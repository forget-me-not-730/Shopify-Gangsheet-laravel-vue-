<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Facades\Image;

class CustomerImage extends Model
{
    protected $fillable = [
        'customer_id',
        'session_id',
        'parent_id',
        'title',
        'name',
        'width',
        'height',
        'extension',
        'resolution',
        'type'
    ];

    protected $appends = [
        'url',
        'thumb_url'
    ];

    public function getPathAttribute(): string
    {
        if ($this->isVector()) {
            return "images/$this->name.svg";
        } else {
            return "images/$this->name.png";
        }
    }

    public function getUrlAttribute(): string
    {
        return spaces()->url($this->path);
    }

    public function isVector(): bool
    {
        return in_array($this->extension, ['svg', 'ai', 'pdf', 'eps']);
    }

    public function getThumbUrlAttribute(): string
    {
        if ($this->isVector()) {
            return $this->url;
        }

        if ($this->width < 300) {
            return $this->url;
        }

        return route('asset.thumb-image', ['image_name' => $this->name]);
    }

    static public function makeThumbImage($image_name): bool
    {
        $name = explode('.', $image_name)[0];
        $customerImage = CustomerImage::where('name', $name)->first();

        if ($customerImage) {
            $image = Image::make($customerImage->url);

            $width = $image->getWidth();
            $height = $image->getHeight();

            $customerImage->update([
                'width' => $width,
                'height' => $height
            ]);

            $resizeWidth = 300;

            if ($width > $resizeWidth) {
                $image->resize($resizeWidth, intval($resizeWidth * $height / $width), function ($constraint) {
                    $constraint->aspectRatio();
                });

                $thumbPath = "images/thumbs/{$name}.png";
                spaces()->put($thumbPath, $image->encode('png'));

                return true;
            }

            return false;
        }

        return false;
    }
}
