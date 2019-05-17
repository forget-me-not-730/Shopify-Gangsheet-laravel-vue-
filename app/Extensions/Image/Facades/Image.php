<?php

namespace App\Extensions\Image\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Extensions\Image\Image make(mixed $data)
 * @method static self configure(array $config)
 * @method static \App\Extensions\Image\Image canvas(int $width, int $height, mixed $background = null)
 * @method static \App\Extensions\Image\Image cache(\Closure $callback, int $lifetime = null, boolean $returnObj = false)
 */
class Image extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'image';
    }
}
