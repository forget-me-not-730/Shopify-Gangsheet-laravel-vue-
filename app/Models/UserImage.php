<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class UserImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'id',
        'category_id',
        'user_id',
        'title',
        'version',
        'original_name',
        'best_seller',
        'height',
        'width',
        'status',
        'order',
        'mime_type'
    ];

    protected $appends = [
        'url',
        'preview_url',
        'original_url'
    ];

    function getUrlAttribute(): string
    {
        $path = "gallery/{$this->user_id}/watermark_$this->name";
        return spaces()->url($path) . "?v={$this->version}";
    }

    function getWatermarkUrlAttribute(): string
    {
        $path = "gallery/{$this->user_id}/watermark_$this->name";
        return spaces()->url($path) . "?v={$this->version}";
    }

    function getOriginalUrlAttribute(): string
    {
        $path = "gallery/$this->user_id/raw/$this->original_name";
        return spaces()->url($path);
    }

    function getPreviewUrlAttribute(): string
    {
        $path = "gallery/{$this->user_id}/preview_$this->name";
        return spaces()->url($path) . "?v={$this->version}";
    }

    function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'user_image_tags', 'user_image_id', 'tag_id', 'id');
    }

    function category(): BelongsTo
    {
        return $this->belongsTo(UserImageCategory::class);
    }

    public function generateWatermarkImage(): void
    {
        try {
            $watermarkText = $this->user->company_name ?? 'Build A Gang Sheet';
            $watermarkOpacity = $this->user->getSetting('watermark_opacity', 0.5);

            $imageUrl = spaces()->url("gallery/$this->user_id/raw/$this->original_name");
            $imageUrl = str_replace('\\', '/', $imageUrl);
            $watermark = Image::make($imageUrl);

            if ($watermark->width() > 50) {

                $font_size = $watermark->width() / strlen($watermarkText) * 2;

                $repeat = intval($watermark->height() / $font_size);
                $margin = $watermark->height() % $font_size / 2;

                for ($y = 0; $y < $repeat; $y++) {
                    $watermark->text($watermarkText, $watermark->width() / 2, $y * $font_size + $margin, function ($font) use ($font_size, $watermarkOpacity) {
                        $font->file(public_path('assets/fonts/Oswald-Regular.ttf'));
                        $font->size($font_size);
                        $font->color([255, 255, 255, $watermarkOpacity]);
                        $font->align('center');
                        $font->valign('top');
                    });
                }

                $imageName = $this->name ?? Str::uuid()->toString() . '.png';

                $watermark_path = "gallery/{$this->user_id}/watermark_$imageName";
                spaces()->put($watermark_path, $watermark->encode('png'));

                $watermark->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $preview_path = "gallery/{$this->user_id}/preview_$imageName";
                spaces()->put($preview_path, $watermark->encode('png'));

                $this->update(['name' => $imageName]);

                $this->user->setSetting([
                    'has_watermark' => true,
                    'has_watermark_preview' => true
                ]);

            } else {
                $this->delete();
                slack_report([
                    "Watermark Image Generation: Invalid Url $imageUrl"
                ]);
            }

        } catch (\Exception $exception) {
            report($exception);

            $this->update(['processing' => false]);
        }
    }
}
