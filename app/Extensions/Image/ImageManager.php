<?php

namespace App\Extensions\Image;

use Intervention\Image\ImageManager as BaseImageManager;

class ImageManager extends BaseImageManager
{
    public function make($data): Image
    {
        $image = parent::make($data);

        return new Image($image->getDriver(), $image->getCore());
    }

    public function canvas($width, $height, $background = null): Image
    {
        $image = parent::canvas($width, $height, $background);

        return new Image($image->getDriver(), $image->getCore());
    }
}
