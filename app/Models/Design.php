<?php

namespace App\Models;

use App\Enums\Queue;
use App\GangSheet\Facades\Dropbox;
use App\GangSheet\Traits\GangSheet;
use App\GangSheet\Traits\HasLog;
use App\Jobs\OutputGangSheet;
use App\Mail\ApproveEditRequest;
use App\Mail\DeclineEditRequest;
use App\Mail\DesignModified;
use App\Mail\SendEditRequest;
use App\Repositories\DesignRepository;
use App\Traits\HasMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;


class Design extends Model
{
    use HasApiTokens, GangSheet, SoftDeletes, HasMetaData, HasLog;

    public $incrementing = false;

    const DIRECTORY = 'gang-sheets/';

    const EDIT_REQUEST_PENDING = 'pending';
    const EDIT_REQUEST_APPROVED = 'approved';
    const EDIT_REQUEST_DECLINED = 'declined';
    const EDIT_REQUEST_PROCESSED = 'processed';

    const STATUS_DRAFT = 'draft';
    const STATUS_CREATED = 'created';
    const STATUS_PROCESSING = 'processing';

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'file_name',
        'customer_id',
        'session_id',
        'product_id',
        'size_id',
        'data',
        'access_token',
        'quantity',
        'order_id',
        'paid',
        'paid_at',
        'type',
        'status',
        'edit_request',
        'downloaded_at'
    ];

    protected $appends = [
        'watermark_url',
        'thumbnail_url',
        'edit_url',
        'download_url',
        'data'
    ];

    protected $casts = [
        'data' => 'json'
    ];

    const TYPE_GANG_SHEET = 1;
    const TYPE_STICKER = 2;
    const TYPE_LASER = 3;
    const TYPE_BUSINESS_CARD = 4;
    const TYPE_BANNER = 5;
    const TYPE_ROLLING_GANG_SHEET = 6;

    function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    function size(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }

    function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    function getDataAttribute($value)
    {
        if (!$this->hasAppended('data')) {
            return json_decode($value, true);
        }

        $json = json_decode($value, true);

        $data = spaces()->get("designs/{$this->id}.json");

        if (empty($data)) {
            $json['meta']['variant']['id'] = $this->variant_id;
            $json['objects'] = [];
        } else {
            $data = json_decode($data, true);

            if ($data['meta'] ?? null) {
                $json = $data;
            } else {
                $json['objects'] = $data;
            }
        }

        return $json;
    }

    function setDataAttribute($value): void
    {
        if (!empty($value['designId'])) {

            spaces()->put("designs/{$value['designId']}.json", json_encode($value));

            if ($value['meta']['variant'] ?? null) {
                $variant = [
                    'price' => $value['meta']['variant']['price'] ?? '0.00',
                    'title' => $value['meta']['variant']['title'] ?? $value['meta']['variant']['label'] ?? null,
                ];

                if ($value['meta']['variant']['width'] ?? null) {
                    $variant['width'] = $value['meta']['variant']['width'];
                }

                if ($value['meta']['variant']['height'] ?? null) {
                    $variant['height'] = $value['meta']['variant']['height'];
                }

                if ($value['meta']['variant']['unit'] ?? null) {
                    $variant['unit'] = $value['meta']['variant']['unit'];
                }
            }

            $this->attributes['data'] = json_encode([
                'meta' => [
                    'variant' => $variant ?? null,
                ]
            ]);
        } else {
            throw new \Exception('Invalid design data');
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING || $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return (bool)($this->paid || $this->paid_at);
    }

    public function confirmPaid(): bool
    {
        if (!$this->paid) {
            $merchant = $this->user;

            if ($merchant->isGangSheetPublic()) {
                return true;
            }

            $balance = $this->commission;

            if (!$merchant->isCustomStore()) {
                $order = $this->order;
                if ($order) {
                    $balance = $order->commission - $order->paid_amount;
                }
            }

            if ($balance > 0) {
                $commission = min($balance, $this->commission);
                if ($merchant->credits < $commission) {
                    return false;
                }
                $merchant->decrement('credits', $commission);
            }

            $this->update([
                'paid' => true,
                'paid_at' => Carbon::now()
            ]);

            $auto_charge_enabled = $merchant->getMetaData('auto_charge_enabled', 0);
            $auto_min_credits = $merchant->getMetaData('auto_min_credits', 2);

            if ($auto_charge_enabled && $merchant->credits < $auto_min_credits) {
                $merchant->chargeUp();
            }
        }

        return true;
    }

    public function getLogPath(): string
    {
        return "gang-sheets/$this->user_id/$this->id/{$this->id}.log";
    }

    public function replaceFolderName($folderName): string
    {
        $orderName = '#';
        $orderId = '0';
        if ($this->order) {
            $orderId = $this->order->id;
            $orderName .= $orderId;
            $orderData = $this->order->data;
            $orderId = $orderData['id'] ?? $orderId;
        }

        $folderName = str_replace('{order_name}', $orderName, $folderName);
        $folderName = str_replace('{order_id}', $orderId, $folderName);

        return str_replace('{product_id}', $this->product_id, $folderName);
    }

    public function getGoogleDriveFolderName(): string
    {
        $folderName = 'gang_sheets/{order_name}';
        $folderName = $this->merchant->getSetting('googleDriveFolderName', $folderName);

        return $this->replaceFolderName($folderName);
    }

    public function getGoogleDrivePath(): string
    {
        return $this->getGoogleDriveFolderName() . '/' . $this->getGangSheetFileName();
    }

    public function getDropboxFolderName(): string
    {
        $folderName = 'gang_sheets';
        $folderName = $this->merchant->getSetting('dropboxFolderName', $folderName);

        return $folderName;
    }

    public function getDropboxPath(): string
    {
        return $this->getDropboxFolderName() . '/' . $this->getGangSheetFileName();
    }

    function getWatermarkPathAttribute(): string
    {
        $extension = $this->user->getSetting('gangSheetFileExtension', '.png');
        return self::DIRECTORY . "$this->user_id/$this->id/watermark" . $extension;
    }

    function getWatermarkUrlAttribute(): string
    {
        return spaces()->url($this->watermark_path);
    }

    function getThumbnailPathAttribute(): string
    {
        return self::DIRECTORY . "$this->user_id/$this->id/thumbnail.png";
    }

    function getThumbnailUrlAttribute(): string
    {
        return spaces()->url($this->thumbnail_path);
    }

    public function getGangSheetPath()
    {
        return $this->image_path;
    }

    function getImagePathAttribute(): string
    {
        $fileName = $this->getGangSheetFileName();
        return self::DIRECTORY . "$this->user_id/$this->id/$fileName";
    }

    function getImageUrlAttribute(): string
    {
        return Storage::disk('spaces')->url($this->image_path) . '?dt=' . ($this->updated_at->timestamp ?? time());
    }

    public function getDownloadUrlAttribute(): string
    {
        $extension = $this->getSetting('gangSheetFileExtension', '.png');
        return route('gs.gang-sheet', $this->id . $extension);
    }

    function getEditUrlAttribute(): string
    {
        return "https://" . $this->merchant->merchantDomain() . "/merchant/orders/design/edit/" . $this->id;
    }

    function getCustomerEditUrlAttribute(): string
    {
        return "https://" . $this->merchant->merchantDomain() . "/builder/design/" . $this->id;
    }

    function getCommissionAttribute(): float
    {
        if ($this->data['meta']['variant'] ?? null) {
            $variant = $this->data['meta']['variant'];
            $square = (int)$variant['width'] * (int)$variant['height'];
            $unit = $variant['unit'] ?? 'in';
        } else {
            $size = $this->size;
            $square = (int)$size->width * (int)$size->height;
            $unit = $size->unit;
        }

        $commission_rate = $this->merchant->commission_rate;

        if ($unit == 'mm') {
            // 1 in = 25.4 mm
            $commission_rate *= 1 / 25.4 / 25.4;
        } else if ($unit == 'cm') {
            // 1 in = 2.54 cm
            $commission_rate *= 1 / 2.54 / 2.54;
        }

        return round($commission_rate * $square, 2);
    }

    public function getGangSheetFileName(): string
    {
        if (!$this->file_name) {

            $order = $this->order;
            $extension = $this->user->getSetting('gangSheetFileExtension', '.png');

            if ($order) {

                $fileName = $this->merchant->getSetting('gangSheetFileName', config('custom.default_file_name'));

                $customerName = $order->name;
                $customerName = str_replace("#", '', $customerName);

                $variantTitle = $this->data['meta']['variant']['title'] ?? $this->data['meta']['variant']['label'] ?? null;

                if (empty($variantTitle) && $this->size) {
                    $size = $this->size;
                    $variantTitle = $size->label;
                }

                $fileName = str_replace("{variant_title}", $variantTitle, $fileName);

                $fileName = str_replace("/", '-', $fileName);
                $fileName = str_replace("{customer_name}", $customerName, $fileName);
                $fileName = str_replace("{order_id}", $order->wc_order_id ?? $order->id, $fileName);
                $fileName = str_replace("{quantity}", $this->quantity, $fileName);
                $fileName = str_replace("{design_name}", $this->name, $fileName);

                $orderName = $order->id;
                $fileName = str_replace("{order_name}", $orderName, $fileName);

                $lineItemNumber = Design::where('order_id', $this->order_id)
                    ->where('item_id', '<', $this->item_id)
                    ->count();

                $fileName = str_replace("{line_item_number}", $lineItemNumber + 1, $fileName);

                $shippingMethod = $order->getShippingMethod();

                $fileName = str_replace("{shipping_method}", $shippingMethod, $fileName);

                $fileName = str_replace("/", '-', $fileName);

                // remove # from file name
                $fileName = str_replace("#", '', $fileName);
                $fileName = str_replace(",", '', $fileName);

                $this->file_name = $fileName . $extension;

                $this->save();
            } else {
                if ($this->merchant->company_name) {
                    $this->file_name = $this->merchant->company_name . '-' . $this->id . $extension;
                } else {
                    $this->file_name = $this->id . $extension;
                }
            }
        }

        return $this->file_name;
    }

    function getDownloadUrl(): object|null
    {
        $file = Storage::disk('spaces')->get(self::DIRECTORY . $this->id . '.png');
        if ($file) {
            $name = $this->getGangSheetFileName();
            $headers = [
                'Content-Type' => 'image/png',
                'Content-Disposition' => "attachment; filename={$name}",
                'filename' => $name
            ];

            return response($file, 200, $headers);
        }

        return null;
    }

    public function sendEditRequest(): void
    {
        $this->edit_request = self::EDIT_REQUEST_PENDING;
        $this->save();

        Mail::to($this->merchant->getSetting('shop_email', $this->merchant->email))
            ->send(new SendEditRequest($this->id));
    }

    public function approveEditRequest(): void
    {
        $this->edit_request = self::EDIT_REQUEST_APPROVED;
        $this->save();

        Mail::to($this->order->email)
            ->send(new ApproveEditRequest($this->id));
    }

    public function declineEditRequest($reason): void
    {
        $this->edit_request = self::EDIT_REQUEST_DECLINED;
        $this->decline_reason = $reason ?? null;
        $this->save();

        Mail::to($this->order->email)
            ->send(new DeclineEditRequest($this->id));
    }

    public function processedEditRequest(): void
    {
        $this->edit_request = self::EDIT_REQUEST_PROCESSED;
        $this->save();

        $shopEmail = $this->merchant->getSetting('editRequestEmail', $this->merchant->getSetting('shop_email', $this->merchant->email));

        Mail::to($shopEmail)
            ->send(new DesignModified($this->id));
    }

    public function getDesignViewUrl(): string
    {
        $baseUrl = config('app.url');

        $query = http_build_query([
            'user_id' => $this->user_id
        ]);

        return $baseUrl . "/builder/view/" . $this->id . "?" . $query;
    }

    public function verifyToken($token): bool
    {
        return $this->access_token === $token;
    }

    public function allowedEdit($token = null): bool
    {
        if (!$this->order_id) {
            return true;
        }

        if ($this->edit_request === self::EDIT_REQUEST_APPROVED) {
            return true;
        }

        if (!$this->merchant->isWooStore()) {
            return true;
        }

        if ($this->verifyToken($token)) {
            return true;
        }

        return false;
    }

    public function generateGangSheetImage($method): void
    {
        OutputGangSheet::dispatch($this->id, [
            'method' => $method,
            'queue' => Queue::GANG_SHEET_THREE->value
        ]);
    }

    public function getGangSheetFileContent(): ?string
    {
        $gangSheetPath = $this->getGangSheetPath();

        if (spaces()->exists($gangSheetPath)) {
            $content = spaces()->get($gangSheetPath);
        } else {
            $gangSheetPath = "gang_sheets/{$this->id}.png";

            if (spaces()->exists($gangSheetPath)) {
                $content = spaces()->get($gangSheetPath);
            }
        }

        return $content ?? null;
    }

    public function uploadGangSheetToDropbox(): void
    {
        $dropboxConnected = $this->user->isDropboxConnected();
        if ($dropboxConnected) {
            $path = $this->getDropboxPath();
            $content = $this->getGangSheetFileContent();
            if ($content) {
                $success = Dropbox::uploadGangSheetImage($this->user_id, $path, $content);

                if (!$success) {
                    $this->setMetaData('dropbox_failed', true);
                } else {
                    $this->removeMetaData('dropbox_failed');
                }
            } else {
                slack_report("Gang sheet not found: {$this->id}");
            }
        }
    }

    public function gangSheetExists(): bool
    {
        return spaces()->exists($this->image_path);
    }

    public function uploadGangSheet(): void
    {
        if ($this->user->getSetting('useUploadDropbox', false)) {
            $this->uploadGangSheetToDropbox();
        }
    }

    public function sendWebhook($webhook): void
    {
        $webhookUrl = $this->user->getSetting($webhook);

        // Send webhook
        if ($webhookUrl) {
            try {
                if ($size = $this->data['meta']['variant'] ?? null) {
                    unset($size['useHiddenVariants']);
                }

                $this->refresh();

                if (!$this->isCompleted()) {
                    throw new \Exception('Design is not completed');
                }

                $data = [
                    'event' => 'design:completed',
                    'design' => DesignRepository::toJson($this),
                ];

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json'
                ])->post($webhookUrl, $data);

                if ($response->status() !== 200) {
                    slack_report("Failed to send webhook: {$webhook} for design: {$this->id}");
                }
            } catch (\Exception $e) {
                report($e);
                slack_report("Failed to send webhook: {$webhook} for design: {$this->id}");
            }
        }
    }

    public function getSetting($key, $default = null)
    {
        if ($this->product ?? null) {
            return $this->product->getSetting(
                $key,
                $this->user->getSetting($key, $default)
            );
        }

        return $this->user->getSetting($key, $default);
    }

    public function getImageSrcFromImageObject($object): string
    {
        $src = $object['src'];
        $cacheKey = hash('sha256', $src);

        return cache()->remember($cacheKey, now()->addMinutes(2), function () use ($src) {
            if (str_starts_with($src, 'http')) {
                $spaceRootUrl = spaces()->url('/');
                [$imageName] = explode('.', basename($src));

                if (str_contains($src, 'gallery')) {
                    $imageName = str_replace('thumbnail_', '', $imageName);
                    $imageName = str_replace('watermark_', '', $imageName);
                    $imageName = str_replace('preview_', '', $imageName);

                    $userImage = UserImage::where('name', "$imageName.png")
                        ->orWhere('original_name', "$imageName.svg")
                        ->withTrashed()
                        ->first();

                    if ($userImage) {
                        $src = $userImage?->originalUrl;
                    }
                } else if (!str_starts_with($src, $spaceRootUrl)) {
                    $path = "images/$imageName.png";
                    if (spaces()->exists($path)) {
                        $src = spaces()->url($path);
                    } else {
                        $path = "images/$imageName.svg";
                        if (spaces()->exists($path)) {
                            $src = spaces()->url($path);
                        }
                    }
                }
            }

            return str_replace('\\', '/', $src);
        });
    }

    public function getPrintFileName()
    {
        return $this->getSetting('printFileName', false);
    }
}
