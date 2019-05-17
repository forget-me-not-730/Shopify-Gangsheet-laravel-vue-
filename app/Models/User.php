<?php

namespace App\Models;

use App\GangSheet\Traits\HasLog;
use App\Notifications\UserPasswordResetNotification;
use App\Traits\HasMetaData;
use App\Traits\HasSlug;
use App\Traits\HasStatus;
use App\Traits\WooTrait;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, HasStatus, HasSlug, WooTrait, HasMetaData, HasLog;

    protected string $slugFromField = 'company_name';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable
        = [
            'shop_uuid',
            'type',
            'name',
            'first_name',
            'last_name',
            'email',
            'phone',
            'password',
            'company_name',
            'website',
            'slug',
            'show_gallery',
            'settings',
            'logo',
            'max_order',
            'commission_rate',
            'credits',
            'status',
        ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden
        = [
            'password',
            'remember_token',
        ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts
        = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'json'
        ];

    protected $appends = [
        'logo_url',
        'shop_name'
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new UserPasswordResetNotification($token));
    }

    function getShopNameAttribute()
    {
        return $this->company_name;
    }

    function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return Storage::disk('spaces')->url('logos/' . $this->logo);
        }

        return null;
    }

    function getMaxOrderAttribute($max_order): float
    {
        if (empty($max_order)) {
            return 12;
        }

        return $max_order;
    }

    function getCommissionRateAttribute($commission_rate)
    {
        return $commission_rate ?? 0.0010;
    }

    function getWebsiteAttribute($website)
    {
        if ($website && !str_starts_with($website, 'http')) {
            return 'https://' . $website;
        }

        return $website;
    }

    function getCreditsAttribute($credits)
    {
        return round($credits, 2);
    }

    function products()
    {
        return $this->hasMany(Product::class);
    }

    function orders()
    {
        return $this->hasMany(Order::class);
    }

    function designs()
    {
        return $this->hasMany(Design::class);
    }

    function scopeAdmin($query)
    {
        return $query->where('type', 'admin');
    }

    function scopeMerchant($query)
    {
        return $query->where('type', '!=', 'admin');
    }

    public function isAdmin(): bool
    {
        return $this->type === 'admin';
    }

    public function merchantDomain()
    {
        if ($this->domain) {
            return $this->domain;
        } else {
            return $this->slug . "." . config('app.domain');
        }
    }

    function isNormalStore(): bool
    {
        return $this->type === 'normal';
    }

    function isWooStore(): bool
    {
        return $this->type === 'woo';
    }

    function isCustomStore(): bool
    {
        return $this->type === 'custom';
    }

    function isGangSheetPublic(): bool
    {
        return $this->credits == -1;
    }

    function isGangSheetPrivate(): bool
    {
        return !$this->isGangSheetPublic();
    }

    public function setSetting($key, $value = null): void
    {
        $settings = $this->settings ?? [];

        if (is_array($key)) {
            foreach ($key as $settingKey => $settingValue) {
                $settings[$settingKey] = $settingValue;
            }
        } else {
            $settings[$key] = $value;
        }

        $this->settings = $settings;
        $this->save();
    }

    public function getSetting($key, $default = null)
    {
        $settings = $this->settings ?? [];

        return $settings[$key] ?? $default;
    }

    public function getPlainTextToken(): string
    {
        $plainTextToken = $this->getSetting('plainTextToken');

        if (empty($plainTextToken)) {
            $token = $this->createToken('woo_access_token');
            $plainTextToken = $token->plainTextToken;
            $this->setSetting('plainTextToken', $plainTextToken);
        }

        return $plainTextToken;
    }

    public function setWatermarkProcessing(bool $status): void
    {
        $this->setSetting('watermark_processing', $status);
        if (!$status) {
            $this->setSetting('watermark_version', $this->getSetting('watermark_version', 1) + 1);
        }
        $this->save();
    }

    public function getStripeCheckoutUrl($amount, $successUrl, $cancelUrl): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $checkoutSession = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Add Credits on ' . config('app.name'),
                        ],
                        'unit_amount' => 100,
                    ],
                    'quantity' => $amount,
                ]
            ],
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'payment_intent_data' => [
                'setup_future_usage' => 'off_session',
            ],
            'metadata' => [
                'user_id' => $this->id
            ],
        ]);

        return $checkoutSession->url;
    }

    public function chargeUp()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $auto_charge_amount = $this->getMetaData('auto_charge_amount');
        $stripe_customer_id = $this->getMetaData('stripe_customer_id');
        $stripe_payment_method = $this->getMetaData('stripe_payment_method');

        try {
            PaymentIntent::create([
                'amount' => $auto_charge_amount * 100,
                'currency' => 'usd',
                'customer' => $stripe_customer_id,
                'payment_method' => $stripe_payment_method,
                'metadata' => [
                    'user_id' => $this->id,
                ],
                'description' => 'Add Credits on ' . config('app.name'),
                'off_session' => true,
                'confirm' => true,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->setMetaData('creditNotification', $e->getMessage());
        }
    }

    public function updateLogo($logoBase64String): void
    {
        preg_match("/data:(.*?);base64,/i", $logoBase64String, $match);
        $type = $match[1];
        $extension = explode('/', $type)[1];
        $image = str_replace("data:$type;base64,", '', $logoBase64String);
        $image = base64_decode($image);
        $name = Str::uuid()->toString() . '.' . $extension;
        Storage::disk('spaces')->put('logos/' . $name, $image);

        $this->update([
            'logo' => $name,
        ]);
    }

    public function isNinja()
    {
        return false;
    }

    public function toString(): string
    {
        return "{$this->id}|{$this->website}|{$this->company_name}";
    }

    public function isGoogleDriveConnected(): bool
    {
        return $this->getSetting('googleDriveConnected', false);
    }

    public function isDropboxConnected()
    {
        return $this->getSetting('dropboxConnected', false);
    }

    public function getDropboxToken()
    {
        return InAppAuthToken::where('identifier', 'dropbox')
            ->where('type', 'gang_sheets')
            ->where('user_id', $this->id)
            ->first();
    }

    public function getEvents()
    {
        $events = $this->getMetaData('events');

        if ($events) {
            return json_decode($events, true);
        }

        return [];
    }

    public function addEvent($event, $data = []): void
    {
        $events = $this->getEvents();

        $events[] = [
            'event' => $event,
            'data' => $data,
            'created_at' => now()->toDateTimeString()
        ];

        $this->setMetaData('events', json_encode($events));
    }

    public function getLogPath(): string
    {
        return "logs/shops/{$this->id}/default.log";
    }
}
