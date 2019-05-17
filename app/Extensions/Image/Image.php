<?php

namespace App\Extensions\Image;

use Intervention\Image\Image as InterventionImage;

class Image extends InterventionImage
{
    public function applyFilters($filters): void
    {
        foreach ($filters as $filter) {
            try {
                switch ($filter['type']) {
                    case 'Gamma':
                        if (isset($filter['gamma']) && is_array($filter['gamma'])) {
                            $this->getCore()->gammaImage($filter['gamma'][0], \Imagick::CHANNEL_RED);
                            $this->getCore()->gammaImage($filter['gamma'][1], \Imagick::CHANNEL_GREEN);
                            $this->getCore()->gammaImage($filter['gamma'][2], \Imagick::CHANNEL_BLUE);
                        }
                        break;
                    case 'Brightness':
                        if (isset($filter['brightness'])) {
                            $this->applyBrightnessContrastFilter($filter['brightness'], 0);
                        }
                        break;
                    case 'Contrast':
                        if (isset($filter['contrast'])) {
                            $this->applyBrightnessContrastFilter(0, $filter['contrast']);
                        }
                        break;
                    case 'Saturation':
                        if (isset($filter['saturation'])) {
                            $this->getCore()->modulateImage(100, ($filter['saturation'] + 1) * 100, 100);
                        }
                        break;
                    case 'HueRotation':
                        if (isset($filter['rotation'])) {
                            $this->getCore()->modulateImage(100, 100, ($filter['rotation'] + 1) * 100);
                        }
                        break;
                    case 'BlendColor':
                        if (isset($filter['color'])) {
                            $this->applyBlendColor($filter['color'], $filter['mode']);
                        }
                        break;
                }
            } catch (\Exception $e) {
                // Do nothing
            }
        }
    }

    /**
     * @throws \ImagickPixelException
     */
    function applyBlendColor($color, $mode): void
    {
        if (!str_starts_with($color, '#')) {
            $color = substr('#' . $color, 0, 7);
        }

        $pixel = new \ImagickPixel($color);
        $color = $pixel->getColor();

        $tr = $color['r'];
        $tg = $color['g'];
        $tb = $color['b'];

        $this->getCore()->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        $this->getCore()->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);

        $iterator = $this->getCore()->getPixelIterator();

        foreach ($iterator as $pixels) {
            foreach ($pixels as $pixel) {
                $pixelColor = $pixel->getColor();

                $r = $pixelColor['r'];
                $g = $pixelColor['g'];
                $b = $pixelColor['b'];

                $a = $pixel->getColorValue(\Imagick::COLOR_ALPHA);

                if ($a > 0) {
                    switch ($mode) {
                        case 'overlay':
                            $newR = $tr < 128 ? (2 * $r * $tr / 255) : (255 - 2 * (255 - $r) * (255 - $tr) / 255);
                            $newG = $tg < 128 ? (2 * $g * $tg / 255) : (255 - 2 * (255 - $g) * (255 - $tg) / 255);
                            $newB = $tb < 128 ? (2 * $b * $tb / 255) : (255 - 2 * (255 - $b) * (255 - $tb) / 255);
                            break;
                        default:
                            $newR = $tr;
                            $newG = $tg;
                            $newB = $tb;
                    }

                    $pixel->setColor("rgba($newR,$newG,$newB,$a)");
                }
            }

            $iterator->syncIterator();
        }
    }

    function applyBrightnessContrastFilter($brightness, $contrast): void
    {
        $brightnessAdjustment = 255 * $brightness;
        $contrastAdjustment = (259 * ($contrast + 1)) / (259 - $contrast * 255);

        $this->getCore()->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        $this->getCore()->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);

        $iterator = $this->getCore()->getPixelIterator();

        foreach ($iterator as $pixels) {
            foreach ($pixels as $pixel) {

                $a = $pixel->getColorValue(\Imagick::COLOR_ALPHA);
                $origColor = $pixel->getColor();

                if ($a > 0) {
                    $origR = $origColor['r'];
                    $origG = $origColor['g'];
                    $origB = $origColor['b'];

                    $newR0 = min(255, max(0, $origR + $brightnessAdjustment));
                    $newG0 = min(255, max(0, $origG + $brightnessAdjustment));
                    $newB0 = min(255, max(0, $origB + $brightnessAdjustment));

                    $newR = min(255, max(0, 128 + ($newR0 - 128) * $contrastAdjustment));
                    $newG = min(255, max(0, 128 + ($newG0 - 128) * $contrastAdjustment));
                    $newB = min(255, max(0, 128 + ($newB0 - 128) * $contrastAdjustment));

                    $pixel->setColor("rgba($newR,$newG,$newB,$a)");
                }
            }
            $iterator->syncIterator();
        }
    }

    function getImageResolution(): int
    {
        try {

            $methods = ['getImageResolution', 'getResolution'];

            foreach ($methods as $method) {
                if (method_exists($this->getCore(), $method)) {
                    $resolution = $this->getCore()->$method();

                    $resolution = min(intval($resolution['x']), intval($resolution['y']));

                    $units = $this->getCore()->getImageUnits();

                    if ($units == \Imagick::RESOLUTION_PIXELSPERCENTIMETER) {
                        $resolution = round_up($resolution * 2.54);
                    }

                    if ($resolution > 0) {
                        return $resolution;
                    }
                }
            }

            return 72;

        } catch (\Exception $exception) {
            // ignore
            return 72;
        }
    }

    public function isTransparent($threshold = 90, $sampleSize = 100000): bool
    {
        $imagick = $this->getCore();
        $imagick->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();

        $stepX = max(1, floor($width / sqrt($sampleSize)));
        $stepY = max(1, floor($height / sqrt($sampleSize)));

        $totalPixels = 0;
        $transparentPixels = 0;

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                try {
                    $pixel = $imagick->getImagePixelColor($x, $y);
                    $a = $pixel->getColorValue(\Imagick::COLOR_ALPHA);

                    // Consider pixels with alpha < 0.05 as transparent
                    if ($a < 0.05) {
                        $transparentPixels++;
                    }

                    $totalPixels++;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        if ($totalPixels > 0) {
            $transparencyPercentage = ($transparentPixels / $totalPixels) * 100;
            return $transparencyPercentage >= $threshold;
        }

        return false;
    }

    public function hasBackground(): bool
    {
        return !$this->isTransparent(10);
    }

    public function setResolution($resolution = 300): void
    {
        try {
            $this->getCore()->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);
            $this->getCore()->setImageResolution($resolution, $resolution);
        } catch (\Exception $exception) {
            report($exception);
        }
    }
}
