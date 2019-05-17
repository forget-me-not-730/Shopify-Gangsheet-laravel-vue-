<?php

namespace App\Models;

use App\Traits\HasSettings;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, HasSettings, HasSlug;

    protected string $slugFromField = 'title';
    protected array $slugUniqueWith = ['user_id'];

    const TYPE_GANG_SHEET = 1;
    const TYPE_STICKER = 2;
    const TYPE_LASER = 3;
    const TYPE_BUSINESS_CARD = 4;
    const TYPE_BANNER = 5;

    const TYPE_ROLLING_GANG_SHEET = 6;

    protected $fillable = [
        'user_id',
        'woo_product_id',
        'title',
        'slug',
        'type',
        'description',
        'redirect_url',
        'button',
        'settings'
    ];

    protected $casts = [
        'button' => 'json',
        'settings' => 'json',
        'type' => 'integer'
    ];

    protected $appends = [
        'art_board_type'
    ];

    public function getArtBoardTypeAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ROLLING_GANG_SHEET => 'rolling-gang-sheet',
            self::TYPE_STICKER => 'sticker',
            self::TYPE_LASER => 'laser',
            self::TYPE_BUSINESS_CARD => 'business-card',
            self::TYPE_BANNER => 'banner',
            default => 'gang-sheet',
        };
    }

    public function isGangSheet(): bool
    {
        return $this->type === self::TYPE_GANG_SHEET;
    }

    public function isRollingGangSheet(): bool
    {
        return $this->type === self::TYPE_ROLLING_GANG_SHEET;
    }

    public function isSticker(): bool
    {
        return $this->type === self::TYPE_STICKER;
    }

    public function isLaser(): bool
    {
        return $this->type === self::TYPE_LASER;
    }

    public function isBusinessCard(): bool
    {
        return $this->type === self::TYPE_BUSINESS_CARD;
    }

    public function isBanner(): bool
    {
        return $this->type === self::TYPE_BANNER;
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class)
            ->orderBy('width')
            ->orderBy('height');
    }

    function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, Design::class, 'product_id', 'id', 'id', 'order_id');
    }

    function getButtonAttribute($button)
    {
        $default = [
            'text' => 'Build your own Gang Sheet',
            'background_color' => '#000000',
            'text_color' => '#FFFFFF'
        ];
        return json_decode($button, false) ?? $default;
    }

    function getRedirectUrlAttribute($redirect_url)
    {
        if ($redirect_url && !str_starts_with($redirect_url, 'http')) {
            return 'https://' . $redirect_url;
        }
        return $redirect_url;
    }
}
