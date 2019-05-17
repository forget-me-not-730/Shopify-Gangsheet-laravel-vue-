<?php

namespace App\GangSheet\Imagick;

use App\GangSheet\Abstracts\Image as AbstractImage;

class Image extends AbstractImage
{
    public function getSourceId(): string
    {
        if ($this->isSvg()) {
            return hash('sha256', json_encode([
                'src' => $this->src,
                'width' => $this->width,
                'height' => $this->width,
            ]));
        } else {
            return hash('sha256', json_encode([
                'src' => $this->src,
            ]));
        }
    }

    public function getImageId(): string
    {
        $transforms = $this->getTransforms();

        if ($this->isPng()) {
            $transforms['width'] = $this->width;
            $transforms['height'] = $this->height;
        }

        if (count($transforms) > 0) {
            return hash('sha256', json_encode([
                'key' => $this->getSourceId(),
                'transforms' => $transforms,
            ]));
        } else {
            return $this->getSourceId();
        }
    }
}
