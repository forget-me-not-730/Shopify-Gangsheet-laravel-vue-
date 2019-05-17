<?php

namespace App\GangSheet\Xml;

use App\GangSheet\Abstracts\Element;
use App\GangSheet\Classes\Svg;

class Outline extends Element
{
    protected string $shape_type = 'rect';
    protected array $path = [];
    protected float $rx = 0;
    protected float $ry = 0;

    protected float $sf = 96 / 300;

    public float $scale = 1;

    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    protected function drawRect(): Svg
    {
        $rect = new Svg('<rect></rect>');
        $rect->addAttribute('x', $this->left);
        $rect->addAttribute('y', $this->top);
        $rect->addAttribute('width', $this->width);
        $rect->addAttribute('height', $this->height);

        $rect->addAttribute('rx', $this->rx * $this->sf);
        $rect->addAttribute('ry', $this->ry * $this->sf);

        return $rect;
    }

    protected function drawEllipse(): Svg
    {
        $ellipse = new Svg('<ellipse></ellipse>');
        $ellipse->addAttribute('cx', $this->centerX);
        $ellipse->addAttribute('cy', $this->centerY);
        $ellipse->addAttribute('rx', $this->rx * $this->sf - $this->stickerOutlineWidth);
        $ellipse->addAttribute('ry', $this->ry * $this->sf - $this->stickerOutlineWidth);

        return $ellipse;
    }

    function getBoundingBox(): array
    {
        $xPoints = [];
        $yPoints = [];

        foreach ($this->path as $command) {
            $points = array_slice($command, 1);
            for ($i = 0; $i < count($points); $i += 2) {
                $xPoints[] = $points[$i];
                $yPoints[] = $points[$i + 1];
            }
        }

        $box = [
            'x' => min($xPoints),
            'y' => min($yPoints),
            'width' => max($xPoints) - min($xPoints),
            'height' => max($yPoints) - min($yPoints)
        ];

        $box['centerX'] = $box['x'] + $box['width'] / 2;
        $box['centerY'] = $box['y'] + $box['height'] / 2;

        return $box;
    }

    protected function drawPath(): Svg
    {
        $boundingBox = $this->getBoundingBox();

        $translateX = $this->centerX - $boundingBox['centerX'];
        $translateY = $this->centerY - $boundingBox['centerY'];

        $scaleX = $this->width / $boundingBox['width'];
        $scaleY = $this->height / $boundingBox['height'];
        $scale = min($scaleX, $scaleY);

        $this->scaleX = $scaleX;
        $this->scaleY = $scaleY;
        $this->scale = $scale;

        $path = '';
        foreach ($this->path as $command) {
            $cmdType = $command[0];
            $cmdParams = array_slice($command, 1);

            //apply scale and translate
            for ($i = 0; $i < count($cmdParams); $i += 2) {
                $cmdParams[$i] = ($cmdParams[$i] - $boundingBox['centerX']) * $scale + $boundingBox['centerX'] + $translateX;
                $cmdParams[$i + 1] = ($cmdParams[$i + 1] - $boundingBox['centerY']) * $scale + $boundingBox['centerY'] + $translateY;
            }

            $path .= $cmdType . ' ' . implode(' ', $cmdParams) . ' ';
        }

        // close path
        $path .= 'Z';

        $path = trim($path);

        $pathSvg = new Svg('<path></path>');
        $pathSvg->setAttribute('d', $path);
        $pathSvg->setAttribute('x', $this->left);
        $pathSvg->setAttribute('y', $this->top);
        $pathSvg->setAttribute('stroke-linecap', 'round');

        return $pathSvg;
    }

    public function toSvg(): Svg
    {
        if ($this->shape_type === 'ellipse') {
            return $this->drawEllipse();
        } elseif ($this->shape_type === 'path') {
            return $this->drawPath();
        } else {
            return $this->drawRect();
        }
    }
}
