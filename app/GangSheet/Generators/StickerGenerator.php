<?php

namespace App\GangSheet\Generators;

use App\GangSheet\Classes\Svg;
use App\GangSheet\Xml\Outline;
use App\GangSheet\Xml\Image as XmlImage;

class StickerGenerator extends InkscapeGenerator
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);
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
    }

    protected function drawOutline($object): void
    {
        $outline = Outline::create(
            array_merge($object, [
            'stickerOutlineWidth' => $this->stickerOutlineWidth
        ]))->toSvg();

        if (!empty($outline)) {
            $defs = $this->canvas->getDefs();

            $clipPath = $defs->addChild('clipPath');
            $clipPath->setAttribute('id', 'cut-line');
            $clipPath->appendChild($outline);

            $strokeWidth = $this->stickerOutlineWidth;
            $strokeColor = $this->stickerOutlineColor;
            $outline->setStroke($strokeWidth, $strokeColor);
            $outline->setAttribute('fill', 'none');
            $style = "fill: none; stroke: {$strokeColor}; stroke-width: {$strokeWidth};";
            $outline->setAttribute('style', $style);
            $outline->setAttribute('label', "CutContour");
            $this->canvas->appendChild($outline);
        }
    }

    protected function drawImage(array $object): void
    {
        $image = XmlImage::create($object);

        $svgImage = $this->canvas->addChild('image');
        $svgImage->addAttribute('href', $image->encode());

        $svgImage->addAttribute('x', $image->left);
        $svgImage->addAttribute('y', $image->top);
        $svgImage->addAttribute('width', $image->width);
        $svgImage->addAttribute('height', $image->height);
        $svgImage->addAttribute('preserveAspectRatio', 'none');
        $svgImage->addAttribute('label', "Image");
    }

    protected function drawObjects(): void
    {
        foreach ($this->objects as $object) {
            $object['x'] = $this->convertDimension($object['x']);
            $object['y'] = $this->convertDimension($object['y']);
            $object['width'] = $this->convertDimension($object['width']);
            $object['height'] = $this->convertDimension($object['height']);
            $object['offsetX'] = -$this->convertDimension($this->width) / 2;
            $object['offsetY'] = -$this->convertDimension($this->height) / 2;

            if ($object['type'] === 'image') {
                $this->log('Drawing background');
                $this->drawImage($object);
            } elseif ($object['type'] === 'text') {
                $this->log('Drawing text');
                $object['fontSize'] = $this->convertDimension($object['fontSize']);
                $this->drawText($object);
                $this->hasText = true;
            } elseif ($object['id'] === 'cut-line') {
                $this->log('Drawing cut-line');
                $this->drawOutline($object);
            }
        }

        foreach ($this->canvas->children() as $child) {
            if ($child->getName() === 'text') {
                $child->addAttribute('clip-path', "url(#cut-line)");
            }
        }
    }

    public function build(): bool
    {
        try {
            $this->createCanvas();

            $this->drawObjects();

            $this->save();

            $this->convertTextsToPath();

            $this->encode();

            return true;
        } catch (\Exception $e) {
            report($e);
            $this->log('Failed to generate sticker on Error: ', $e->getMessage());

            return false;
        } finally {
            if (config('app.server_name') !== 'local') {
                storage()->deleteDirectory($this->design_id);
            }
        }
    }
}
