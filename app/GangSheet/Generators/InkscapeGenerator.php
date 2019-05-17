<?php

namespace App\GangSheet\Generators;

use App\GangSheet\Abstracts\Generator;
use App\GangSheet\Classes\Svg;
use App\GangSheet\Exceptions\GenerationException;
use App\GangSheet\Xml\Text;
use App\GangSheet\Xml\Image as XmlImage;
use App\Services\FontService;
use App\Extensions\Image\Facades\Image as Image;
use Symfony\Component\Process\Process;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InkscapeGenerator extends Generator
{
    protected Svg $canvas;

    protected ?Svg $group = null;

    protected float $sf = 96 / 300;

    protected float $designWidth = 0;
    protected float $designHeight = 0;

    protected bool $hasText = false;

    protected string $svgPath;

    protected function convertDimension($dimension): int
    {
        return intval($dimension * $this->sf);
    }

    protected function getQrCodeSvg(): ?\DOMElement
    {
        if (!$this->printQRLogo['enable']) {
            return null;
        }
        $QRLogoSize = $this->printQRLogo['size'] * 96;
        $tempQRLogoPath = storage()->path($this->design_id . '/qr.png');
        if ($this->printQRLogo['type'] == 'logo' && $this->printQRLogo['logo']) {
            $logoURL = str_replace("\\", "/", $this->printQRLogo['logo']);
            file_put_contents($tempQRLogoPath, file_get_contents($logoURL));
        } else if ($this->printQRLogo['type'] == 'qr' || !file_exists($tempQRLogoPath)) {
            QrCode::format('png')
                ->size($QRLogoSize)
                ->generate($this->printQRLogo['qr'], $tempQRLogoPath);
        }

        if (!file_exists($tempQRLogoPath)) {
            return null;
        }

        $imageData = file_get_contents($tempQRLogoPath);
        $base64Image = base64_encode($imageData);
        $imageMimeType = mime_content_type($tempQRLogoPath);

        $imageSvg = new \SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $image = $imageSvg->addChild('image');
        $image->addAttribute('href', 'data:' . $imageMimeType . ';base64,' . $base64Image);
        $image->addAttribute('width', $QRLogoSize);
        $image->addAttribute('height', $QRLogoSize);

        return dom_import_simplexml($imageSvg);
    }

    protected function getFileNameText(float $xPosition, float $yPosition, float $fontSize)
    {
        $fileName = str_replace('&nbsp;', ' ', $this->fileName);
        $fileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');

        $attributes = [
            'text' => $fileName,
            'x' => $xPosition + $this->designWidth / 2,
            'y' => $yPosition + ($this->fileNameHeight * 96 - $fontSize / 2) / 2,
            'width' => $this->designWidth,
            'height' => $this->fileNameHeight * 96,
            'fillColor' => '#000000',
            'fontSize' => $fontSize,
            'fontFamily' => 'Oswald',
            'textAnchor' => 'middle',
        ];

        return Text::create($attributes)->toSvg()[0];
    }

    protected function calculateDimensions(): array
    {
        $fileNameHeight = $this->fileNameHeight * 96;
        $QRLogoSize = $this->printQRLogo['size'] * 96;
        $QrLogoPadding = 10;

        $qrElement = $this->getQrCodeSvg();
        $printShopQRLogoInLine = $qrElement && $this->printQRLogo['position'] === 'inline';
        $printShopQRLogoNextLine = $qrElement && $this->printQRLogo['position'] === 'next';

        $maxFileNameWidth = $printShopQRLogoInLine ? 0.9 * ($this->designWidth - $QRLogoSize) : 0.9 * $this->designWidth;
        $lengthOfFileName = strlen($this->fileName);
        $maxFontSize = intval($maxFileNameWidth / $lengthOfFileName * 2.5);
        $fontSize = min(intval($fileNameHeight / 2), $maxFontSize);

        $singleSectionHeight = $printShopQRLogoNextLine
            ? $QRLogoSize + $fileNameHeight + $QrLogoPadding
            : ($printShopQRLogoInLine
                ? max($fileNameHeight, $QRLogoSize + $QrLogoPadding * 2)
                : $fileNameHeight);

        return [
            'fontSize' => $fontSize,
            'singleSectionHeight' => $singleSectionHeight,
            'QRLogoSize' => $QRLogoSize,
            'QrLogoPadding' => $QrLogoPadding,
            'printShopQRLogoInLine' => $printShopQRLogoInLine,
            'printShopQRLogoNextLine' => $printShopQRLogoNextLine
        ];
    }

    protected function drawFileName(): void
    {
        if (!$this->printFileName) {
            $this->addClipPathToGroup();
            return;
        }

        $this->log('Creating SVG with file name');

        $dims = $this->calculateDimensions();
        $qrElement = $this->getQrCodeSvg();

        $newHeight = $this->designHeight;
        if ($this->printFileNamePosition === 'both') {
            $newHeight += $dims['singleSectionHeight'] * 2;
        } else {
            $newHeight += $dims['singleSectionHeight'];
        }

        $this->canvas->setHeight($newHeight);

        $clipY = 0;
        if ($this->printFileNamePosition === 'top' || $this->printFileNamePosition === 'both') {
            $xPosition = $dims['printShopQRLogoInLine']
                ? ($this->designWidth - $dims['QRLogoSize']) / 2
                : $this->designWidth / 2;

            $yPosition = $dims['printShopQRLogoInLine']
                ? $dims['QrLogoPadding'] + ($dims['QRLogoSize'] - $this->fileNameHeight * 96) / 2
                : 0;

            $this->canvas->appendChild($this->getFileNameText($xPosition, $yPosition, $dims['fontSize']));

            if ($qrElement) {
                $qrClone = $qrElement->cloneNode(true);
                $textWidth = $dims['printShopQRLogoInLine'] ? $dims['fontSize'] * strlen($this->fileName) * 0.42 : 0;
                $qrClone->setAttribute('x', ($this->designWidth - $dims['QRLogoSize'] + $textWidth) / 2);
                $qrClone->setAttribute('y', $dims['printShopQRLogoNextLine']
                    ? $this->fileNameHeight * 96
                    : $dims['QrLogoPadding']);
                $this->canvas->appendChild($qrClone);
            }

            $clipY = $dims['singleSectionHeight'];
        }

        $this->group->setY($clipY);
        $this->addClipPathToGroup($clipY);

        if ($this->printFileNamePosition === 'bottom' || $this->printFileNamePosition === 'both') {
            $bottomY = $this->designHeight;
            if ($this->printFileNamePosition === 'both') {
                $bottomY += $dims['singleSectionHeight'];
            }

            $xPosition = $dims['printShopQRLogoInLine']
                ? ($this->designWidth - $dims['QRLogoSize']) / 2
                : $this->designWidth / 2;

            $yPosition = $bottomY + ($dims['printShopQRLogoInLine']
                    ? $dims['QrLogoPadding'] + ($dims['QRLogoSize'] - $this->fileNameHeight * 96) / 2
                    : 0);

            $this->canvas->appendChild($this->getFileNameText($xPosition, $yPosition, $dims['fontSize']));

            if ($qrElement) {
                $qrClone = $qrElement->cloneNode(true);
                $textWidth = $dims['printShopQRLogoInLine'] ? $dims['fontSize'] * strlen($this->fileName) * 0.42 : 0;
                $qrClone->setAttribute('x', ($this->designWidth - $dims['QRLogoSize'] + $textWidth) / 2);
                $qrClone->setAttribute('y', $bottomY + ($dims['printShopQRLogoNextLine']
                        ? $this->fileNameHeight * 96
                        : $dims['QrLogoPadding']));
                $this->canvas->appendChild($qrClone);
            }
        }
    }

    protected function drawImage(array $object): void
    {
        $image = XmlImage::create($object);
        $imageId = $image->getImageId();

        if ($image->width < 1 || $image->height < 1) {
            return;
        }

        $defs = $this->canvas->getDefs();
        $imageDef = null;

        foreach ($defs->children() as $child) {
            if ($child['id'] == $imageId) {
                $imageDef = $child;
                break;
            }
        }

        if (!$imageDef) {
            $image->cache(storage()->path($this->design_id));
            $imageDef = $image->toSvg();
            $defs->appendChild($imageDef);
        }

        if ($this->group) {
            $useImage = $this->group->addChild('use');
        } else {
            $useImage = $this->canvas->addChild('use');
        }

        $useImage->addAttribute('href', "#{$imageId}");

        $scaleX = $image->width / $imageDef['width'];
        $scaleY = $image->height / $imageDef['height'];

        $x = $image->left / $scaleX;
        $y = $image->top / $scaleY;

        $useImage->addAttribute('x', $x);
        $useImage->addAttribute('y', $y);
        $useImage->addAttribute('transform', "rotate($image->angle, $image->centerX, $image->centerY) scale($scaleX, $scaleY)");

        if ($this->isPNG()) {
            $useImage->addAttribute('preserveAspectRatio', 'none');
        }
    }

    protected function drawText(array $object): void
    {
        $text = Text::create($object);
        $svg = $text->toSvg();
        foreach ($svg as $textSvg) {
            $transform = "rotate($text->angle, $text->centerX, $text->centerY)";
            $textSvg->addAttribute('transform', $transform);

            if ($this->group) {
                $this->group->appendChild($textSvg);
            } else {
                $this->canvas->appendChild($textSvg);
            }
        }
    }

    protected function addClipPathToGroup($clipY = 0): void
    {
        $designWidth = $this->designWidth;
        $designHeight = $this->designHeight;

        $clipPath = $this->canvas->getDefs()->addChild('clipPath');
        $clipPath->addAttribute('id', 'gang-sheet-clip-path');
        $polygon = $clipPath->addChild('polygon');
        $points = "0 $clipY, $designWidth $clipY, $designWidth " . ($designHeight + $clipY) . ", 0 " . ($designHeight + $clipY);
        $polygon->addAttribute('points', $points);
        $this->group->addAttribute('clip-path', 'url(#gang-sheet-clip-path)');
    }

    protected function createCanvas(): void
    {
        if (!storage()->exists($this->design_id)) {
            storage()->makeDirectory($this->design_id);
        }

        $this->designWidth = $this->convertDimension($this->getDesignWidth());
        $this->designHeight = $this->convertDimension($this->getActualHeight());
        $this->svgPath = storage()->path($this->design_id . '/gang_sheet.svg');

        $this->log('Creating SVG canvas with width ' . $this->designWidth . ' and height ' . $this->designHeight);
        $this->canvas = Svg::create($this->designWidth, $this->designHeight);

        $this->group = $this->canvas->addChild('svg');
        $this->group->addAttribute('x', 0);
        $this->group->addAttribute('y', 0);
    }

    protected function drawObjects(): void
    {
        $this->log('Drawing objects into SVG');
        foreach ($this->objects as $object) {
            $object['x'] = $this->convertDimension($object['x']);
            $object['y'] = $this->convertDimension($object['y']);
            $object['width'] = $this->convertDimension($object['width']);
            $object['height'] = $this->convertDimension($object['height']);
            $object['offsetX'] = -$this->convertDimension($this->width) / 2;
            $object['offsetY'] = -$this->convertDimension($this->height) / 2;

            if ($object['type'] === 'image') {
                $this->log("Drawing image object: " . $object['src']);
                $this->drawImage($object);
            } elseif ($object['type'] === 'text') {
                $object['fontSize'] = $this->convertDimension($object['fontSize']);
                $this->log("Drawing text object: " . $object['text']);
                $this->drawText($object);
                $this->hasText = true;
            }
        }
    }

    protected function save(): void
    {
        $this->log('Saving SVG file');
        $this->canvas->asXML($this->svgPath);
    }

    protected function convertTextsToPath(): void
    {
        if ($this->hasText) {
            // Convert text to paths to fix non-standard fonts cut-off issue
            $this->log('Converting text to paths');
            $convertedSvgPath = storage()->path($this->design_id . '/gang_sheet_converted.svg');

            $process = Process::fromShellCommandline("inkscape $this->svgPath -T -o $convertedSvgPath", timeout: 600);
            $process->run();

            if ($process->isSuccessful()) {
                $this->log('Waiting for converted SVG file saved');
                if ($this->waitForFile($convertedSvgPath)) {
                    $this->svgPath = $convertedSvgPath;
                }
            }
        }
    }

    /**
     * @throws GenerationException
     */
    protected function encode(): void
    {
        $this->log('Converting SVG to output format');
        $outputFilePath = storage()->path($this->design_id . '/gang_sheet' . $this->fileExtension);

        $command = "inkscape $this->svgPath -o $outputFilePath -d 300";
        $process = Process::fromShellCommandline($command, timeout: 600);
        $process->run();

        if ($process->isSuccessful()) {
            $this->log('Waiting for PNG file saved');
            if (!$this->waitForFile($outputFilePath)) {
                throw new GenerationException('Failed to generate gang sheet for design');
            }
        } else {
            throw new GenerationException('Failed to generate gang sheet in output format');
        }

        $this->content = file_get_contents($outputFilePath);
    }

    public function encodeWatermark(): void
    {
        if ($this->generateWatermark) {
            $this->log('Adding watermark to gang sheet');

            $image = Image::make($this->content);

            $image->resize(1000, 1000, function ($constraint) {
                $constraint->aspectRatio();
            });

            $width = $image->width();
            $height = $image->height();

            $watermark_text = 'Build A Gang Sheet';
            $font_size = min($width, $height) / strlen($watermark_text) * 2;

            $image->text($watermark_text, $width / 2, $height / 2, function ($font) use ($font_size) {
                $font->file($this->fontPath);
                $font->size($font_size);
                $font->color([255, 255, 255]);
                $font->align('center');
                $font->valign('middle');
                $font->angle(45);
            });

            $this->watermark = $image->encode('png');
        }
    }

    public function build(): bool
    {
        try {
            FontService::installFont('Oswald');

            $this->createCanvas();

            $this->drawObjects();

            $this->drawFileName();

            $this->save();

            $this->convertTextsToPath();

            $this->encode();

            $this->encodeWatermark();

            if ($this->isPNG()) {
                $this->log('PNG file is saved and Checking if gang sheet is blank');
                $image = Image::make($this->content);
                $isBlank = $image->isTransparent();

                if ($isBlank) {
                    throw new GenerationException('Failed to generate gang sheet as it is blank');
                }
            }

            return true;
        } catch (\Exception $exception) {
            report($exception);
            $this->log('Failed to generate on Error: ', $exception->getMessage());

            return false;
        }
    }
}
