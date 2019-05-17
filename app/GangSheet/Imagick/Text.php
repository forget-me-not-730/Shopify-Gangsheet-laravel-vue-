<?php

namespace App\GangSheet\Imagick;

use App\Extensions\Image\Image;
use App\Extensions\Image\Facades\Image as AppImage;
use App\GangSheet\Abstracts\Text as AbstractText;
use App\Models\Font;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;

class Text extends AbstractText
{
    /**
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     * @throws ImagickException
     */
    private function makeImagick(): void
    {
        $imagick = new Imagick();
        $draw = new ImagickDraw();

        $fillPixel = new ImagickPixel($this->fillColor);
        $draw->setFillColor($fillPixel);
        $strokePixel = new ImagickPixel($this->strokeColor ?? '');
        $draw->setStrokeColor($strokePixel);

        $strokeWidth = $this->strokeWidth * $this->scaleY;
        $draw->setStrokeWidth($strokeWidth);

        $fontSize = $this->fontSize * $this->scaleY;
        $draw->setFontSize($fontSize);

        $backgroundPixel = new ImagickPixel($this->backgroundColor);
        $draw->setTextUnderColor($backgroundPixel);

        $draw->setGravity(Imagick::GRAVITY_NORTHWEST);

        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);

        $draw->setTextAlignment(Imagick::ALIGN_LEFT);

        if ($this->fontWeight === 'bold') {
            $draw->setFontWeight(700);
        } else {
            $draw->setFontWeight(400);
        }

        if ($this->fontStyle === 'italic') {
            $draw->setFontStyle(Imagick::STYLE_ITALIC);
        } else {
            $draw->setFontStyle(Imagick::STYLE_NORMAL);
        }

        if ($this->underline) {
            $draw->setTextDecoration(Imagick::DECORATION_UNDERLINE);
        }

        $fontPath = storage_path("app/fonts/{$this->fontFamily}-{$this->fontWeight}-{$this->fontStyle}.ttf");

        if (!file_exists($fontPath)) {

            $font = Font::where('name', $this->fontFamily)
                ->where('weight', $this->fontWeight)
                ->where('style', $this->fontStyle)
                ->first();

            if (empty($font)) {
                $font = Font::where('name', $this->fontFamily)
                    ->first();
            }

            if ($font) {
                $fileName = basename($font->file_url);
                $contents = spaces()->get('fonts/' . $fileName);

                // Ensure the directory exists
                $directoryPath = dirname($fontPath);
                if (!file_exists($directoryPath)) {
                    mkdir($directoryPath, 0755, true);
                }

                file_put_contents($fontPath, $contents);
            }
        }

        if (file_exists($fontPath)) {
            $draw->setFont($fontPath);
        }


        $this->draw = $draw;
        $this->imagick = $imagick;
    }

    /**
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    private function getImagick(): Imagick
    {
        if ($this->imagick === null) {
            $this->makeImagick();
        }
        return $this->imagick;
    }

    /**
     * @throws ImagickException
     * @throws ImagickDrawException
     * @throws ImagickPixelException
     */
    public function toImage(): Image
    {
        $textImage = $this->getImagick();

        $measurements = $this->getMeasurements();

        $width = $measurements['textWidth'];
        $height = $measurements['textHeight'];

        $textImage->newImage($width, $height, $this->backgroundColor);

        $lineCount = count($this->textLines);
        $lineHeight = intval($measurements['textHeight'] / $lineCount);

        foreach ($this->textLines as $lineNumber => $lineText) {
            if (trim($lineText)) {
                $offsetX = 0;
                if (!empty($this->leftLines[$lineNumber])) {
                    $offsetX = intval($this->leftLines[$lineNumber]);
                }

                $offsetY = $lineHeight * $lineNumber;
                $measurements = $this->getMeasurements($lineNumber);
                $ascenderRatio = (($measurements['ascender'] - $this->strokeWidth * 2) / $measurements['textHeight']);
                $offsetY += intval($lineHeight * $ascenderRatio);

                $textImage->annotateImage($this->draw, $offsetX, $offsetY, 0, $lineText);
            }
        }

        $textImage->trimImage(0);
        $textImage->resizeImage($this->width, $this->height, Imagick::FILTER_LANCZOS, 1);

        $textImage->rotateImage(new ImagickPixel('#00000000'), $this->angle);

        if ($this->flipX) {
            $textImage->flopImage();
        }
        if ($this->flipY) {
            $textImage->flipImage();
        }

        return AppImage::make($textImage);
    }

    public function getMeasurements($lineNumber = null): array
    {
        $text = $this->textLines[$lineNumber] ?? $this->text;

        $key = $text . $this->fontFamily . $this->fontSize . $this->fontStyle . $this->fontWeight;
        $key = md5($key);

        return cache()->remember($key, now()->addMinutes(30), function () use ($text) {
            return $this->getImagick()->queryFontMetrics($this->draw, $text);
        });
    }
}
