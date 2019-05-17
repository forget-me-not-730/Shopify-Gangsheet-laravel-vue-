<?php

namespace App\GangSheet\Generators;

use App\Extensions\Image\Facades\Image;
use App\Extensions\Image\Image as AppImage;
use \Intervention\Image\Image as InterventionImage;
use App\GangSheet\Abstracts\Generator;
use App\GangSheet\Classes\Svg;
use App\GangSheet\Imagick\Image as ImageElement;
use App\GangSheet\Imagick\Text as TextElement;
use App\GangSheet\Exceptions\GenerationException;
use Symfony\Component\Process\Process;
use ImagickDrawException;
use ImagickException;
use ImagickPixelException;
use Imagick;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ImagickGenerator extends Generator
{
    protected AppImage|InterventionImage $canvas;

    protected array $images = [];

    /**
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    protected function drawText(array $object): bool
    {
        $text = TextElement::create($object);
        $img = $text->toImage();
        $this->canvas->insert($img, 'center', $text->x, $text->y);
        return true;
    }

    protected function drawImage(array $object): bool
    {
        $element = ImageElement::create($object);
        $sourceId = $element->getSourceId();
        $imageId = $element->getImageId();

        if ($element->width < 1 || $element->height < 1) {
            return false;
        }

        if (!isset($this->images[$sourceId])) {
            $element->cache(storage()->path($this->design_id));

            if ($element->isSvg()) {
                $svgContent = file_get_contents($element->getSrc());
                $xml = Svg::loadFromContent($svgContent);
                if (empty($xml['error'])) {
                    $xml['width'] = $element->width;
                    $xml['height'] = $element->height;
                    $svgContent = $xml->asXML();
                }

                file_put_contents($element->cachePath, $svgContent);
            }

            $image = Image::make($element->getSrc());
            $this->images[$sourceId] = $image;
        }

        if (!isset($this->images[$imageId])) {
            $image = clone $this->images[$sourceId];

            $image->resize($element->width, $element->height);

            if ($element->flipX) {
                $image->flip('h');
            }

            if ($element->flipY) {
                $image->flip('v');
            }

            if ($element->angle) {
                $image->rotate(-$element->angle);
            }

            if (count($element->filters) > 0) {
                $image->applyFilters($object['filters']);
            }

            $this->images[$imageId] = $image;
        }

        $image = $this->images[$imageId];

        $this->canvas->insert($image, 'center', $element->x, $element->y);

        return true;
    }

    /**
     * @throws ImagickException
     * @throws ImagickPixelException
     * @throws ImagickDrawException
     */
    protected function drawObjects(): void
    {
        foreach ($this->objects as $object) {
            if (!$this->printPattern && $object['isPattern']) {
                continue;
            } elseif ($object['type'] === 'image') {
                $this->log("Drawing image at x: {$object['x']}, y: {$object['y']}, src: {$object['src']}");
                $this->drawImage($object);
            } elseif ($object['type'] === 'text') {
                $this->log("Drawing text at x: {$object['x']}, y: {$object['y']}, src: {$object['text']}");
                $this->drawText($object);
            }
        }
    }

    protected function getQrCodeImage(): ?AppImage
    {
        if (!$this->printQRLogo['enable']) {
            return null;
        }

        $QRLogoSize = $this->printQRLogo['size'] * 300;

        if ($this->printQRLogo['type'] == 'logo' && $this->printQRLogo['logo']) {
            $logoURL = str_replace("\\", "/", $this->printQRLogo['logo']);
            $qrImage = Image::make($logoURL);
        }

        if ($this->printQRLogo['type'] == 'qr' || empty($qrImage)) {
            $tempFile = storage()->path($this->design_id . "/qr.png");
            QrCode::format('png')
                ->size($QRLogoSize)
                ->generate($this->printQRLogo['qr'], $tempFile);
            if (file_exists($tempFile)) {
                $qrImage = Image::make($tempFile);
            }
        }

        if (!empty($qrImage)) {
            if ($qrImage->getWidth() > $qrImage->getHeight()) {
                $qrImage->resize($QRLogoSize, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } else {
                $qrImage->resize(null, $QRLogoSize, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            return $qrImage;
        }

        return null;
    }

    protected function getFileNameImage(): InterventionImage
    {
        $fileNameSize = $this->fileNameHeight * 300;
        $QRLogoPadding = 30;
        $qrImage = $this->getQrCodeImage();
        $QRLogoSize = $qrImage ? min($this->printQRLogo['size'] * 300, $qrImage->getHeight()) : $this->printQRLogo['size'] * 300;

        $printShopQRLogoInLine = $this->printQRLogo['position'] === 'inline' && $qrImage;

        $fileNameHeight = $fileNameSize;
        if ($qrImage) {
            if ($printShopQRLogoInLine) {
                $fileNameHeight = max($fileNameSize, $QRLogoSize + $QRLogoPadding * 2);
            } else {
                $fileNameHeight = $fileNameSize + $QRLogoSize + $QRLogoPadding;
            }
        }

        $width = $this->getDesignWidth();
        $fileNameImage = Image::canvas($width, $fileNameHeight);

        $maxFileNameWidth = $printShopQRLogoInLine ? 0.8 * ($width - $qrImage->getWidth()) : 0.8 * $width;
        $lengthOfFileName = strlen($this->fileName);
        $maxFontSize = intval($maxFileNameWidth / $lengthOfFileName * 2.5);

        $fontSize = intval($fileNameSize / 2);
        $fontSize = min($fontSize, $maxFontSize);
        $textOffsetTop = $printShopQRLogoInLine ? (max($fileNameSize, $QRLogoSize + $QRLogoPadding) - $fontSize) / 2 : ($fileNameSize - $fontSize) / 2;
        $xPosition = $printShopQRLogoInLine ? ($width - $qrImage->getWidth()) / 2 : $width / 2;

        $fileNameImage->text($this->fileName, $xPosition, $textOffsetTop, function ($font) use ($fontSize) {
            $font->file($this->fontPath);
            $font->size($fontSize);
            $font->align('center');
            $font->valign('top');
        });

        if ($qrImage) {
            if ($printShopQRLogoInLine) {
                $qrY = $fileNameSize > $QRLogoSize ? intval($textOffsetTop + ($fontSize - $QRLogoSize) / 2) : $QRLogoPadding;
                $textWidth = $fontSize * strlen($this->fileName) * 0.44;
                $qrX = intval($xPosition + $textWidth / 2);
            } else {
                $qrY = intval($fileNameSize);
                $qrX = intval(($width - $QRLogoSize) / 2);
            }
            $fileNameImage->insert($qrImage, 'top-left', $qrX, $qrY);
        }

        return $fileNameImage;
    }

    protected function drawFileName(): void
    {
        if ($this->printFileName) {
            $this->log('Adding file name: ' . $this->fileName);

            $designImage = $this->canvas->getCore();

            $fileNameImage = $this->getFileNameImage();
            $fileNameHeight = $fileNameImage->height();

            if ($this->printFileNamePosition === 'both') {
                $fileNameHeight *= 2;
            }

            $width = $this->getDesignWidth();
            $oldHeight = $this->canvas->height();

            $newHeight = $oldHeight + $fileNameHeight;
            $this->canvas = Image::canvas($width, $newHeight);

            if ($this->printFileNamePosition === 'bottom') {
                $this->canvas->insert($designImage, 'top-left', 0, 0);
            } else {
                $this->canvas->insert($designImage, 'top-left', 0, $fileNameImage->height());
            }

            $printFileNameOnTop = $this->printFileNamePosition === 'top' || $this->printFileNamePosition === 'both';
            $printFileNameOnBottom = $this->printFileNamePosition === 'bottom' || $this->printFileNamePosition === 'both';

            if ($printFileNameOnTop) {
                $this->canvas->insert($fileNameImage, 'top-left', 0, 0);
            }

            if ($printFileNameOnBottom) {
                $y = $printFileNameOnTop ? $oldHeight + $fileNameImage->height() : $oldHeight;
                $this->canvas->insert($fileNameImage, 'top-left', 0, $y);
            }
        }
    }

    protected function createCanvas(): void
    {
        $width = $this->getDesignWidth();
        $height = $this->getDesignHeight();

        $this->log("Creating canvas with width {$width} and height {$height}");
        $this->canvas = Image::canvas($this->width, $height);
        $this->canvas->getCore()->setImageColorspace(Imagick::COLORSPACE_RGB);
    }

    protected function encodeWatermark(): void
    {
        if ($this->generateWatermark) {
            $this->log('Adding watermark to gang sheet');

            $width = $this->getDesignWidth();
            $height = $this->getActualHeight();
            $watermark_text = 'Build A Gang Sheet';
            $font_size = min($width, $height) / strlen($watermark_text) * 2;

            $this->canvas->text($watermark_text, $width / 2, $height / 2, function ($font) use ($font_size) {
                $font->file($this->fontPath);
                $font->size($font_size);
                $font->color([255, 255, 255]);
                $font->align('center');
                $font->valign('middle');
                $font->angle(45);
            });

            $this->canvas->resize(1000, 1000, function ($constraint) {
                $constraint->aspectRatio();
            });

            $this->watermark = $this->canvas->encode('png');
        }
    }

    public function build(): bool
    {
        try {
            $this->createCanvas();

            $this->drawObjects();

            // fix greyscale issue
            $this->canvas->pixel('#000001', intval($this->getDesignWidth() / 2), intval($this->getActualHeight() / 2));

            if ($this->getDesignHeight() > $this->getActualHeight()) {
                $this->log("Cropping canvas with actual height {$this->getActualHeight()}");
                $this->canvas->crop($this->getDesignWidth(), $this->getActualHeight(), 0, 0);
            }

            $this->drawFileName();
            $this->canvas->setResolution();

            $this->canvas->getCore()->setImageColorspace(Imagick::COLORSPACE_SRGB);

            if ($this->isTIFF()) {
                $pngFilePath = storage()->path($this->design_id . "/gang_sheet.png");
                file_put_contents($pngFilePath, $this->canvas->encode('png'));
                $this->log('Converting PNG to TIFF');
                $tiffFilePath = storage()->path($this->design_id . '/gang_sheet.tiff');

                $command = "magick $pngFilePath -colorspace CMYK -density 300 -units PixelsPerInch -compress LZW $tiffFilePath";
                $process = Process::fromShellCommandline($command, timeout: 600);
                $process->run();

                if ($process->isSuccessful()) {
                    $this->log('Waiting for TIFF file saved');
                    if (!$this->waitForFile($tiffFilePath)) {
                        throw new GenerationException('Failed to generate gang sheet for design');
                    }
                } else {
                    throw new GenerationException('Failed to generate gang sheet in output format');
                }

                $this->content = file_get_contents($tiffFilePath);
            } else {
                $this->log('Encoding canvas to PNG');
                $this->content = $this->canvas->encode('png');
            }

            $this->encodeWatermark();
        } catch (\Exception $exception) {
            report($exception);
            $this->log('Failed to generate on Error: ', $exception->getMessage());
            return false;
        }

        return true;
    }
}
