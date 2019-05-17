<?php

namespace App\GangSheet\Xml;

use App\GangSheet\Abstracts\Text as AbstractText;
use App\GangSheet\Classes\Svg;
use App\GangSheet\Imagick\Text as ImagickText;
use App\Services\FontService;

class Text extends AbstractText
{
    public function hex8ToHex6AndOpacity($hex8): array
    {
        // Remove the hash at the start if it's there
        $hex8 = str_replace('#', '', $hex8);

        // Extract the components
        $color = substr($hex8, 0, 6);
        $alpha = substr($hex8, 6, 2);

        // Combine red, green, and blue to get hex6
        $hex6 = "#$color";

        // Convert alpha to opacity (0 to 1)
        if (empty($alpha)) {
            $opacity = 1;
        } else {
            $opacity = hexdec($alpha) / 255;
        }

        return [$hex6, $opacity];
    }

    public function toSvg(): array
    {
        $strokeWidth = intval($this->strokeWidth * $this->scaleY);
        $fontSize = intval($this->fontSize * $this->scaleY);

        // Ensure front installed
        FontService::installFont($this->fontFamily);

        $lineCount = count($this->textLines);
        $lineHeight = intval($this->height / $lineCount);

        $svg = [];
        foreach ($this->textLines as $lineNumber => $lineText) {
            if (trim($lineText)) {
                $offsetX = 0;
                if (!empty($object['leftLines'][$lineNumber])) {
                    $offsetX = intval($object['leftLines'][$lineNumber]);
                }

                if (empty($object['baseLines'][$lineNumber])) {
                    $measurements = ImagickText::create($this->toArray())->getMeasurements();
                    $ascenderRatio = ($measurements['ascender'] - $strokeWidth * 2) / $measurements['textHeight'];
                    $offsetY = intval($lineHeight * $ascenderRatio);
                } else {
                    $offsetY = intval($object['baseLines'][$lineNumber] + $object['height'] / 2);
                }

                $textX = $this->left + $offsetX;
                $textY = $this->top + $offsetY;

                $lineText = htmlspecialchars("$lineText", ENT_QUOTES, 'UTF-8');
                $textSvg = new Svg("<text>$lineText</text>");
                $textSvg->addAttribute('x', $textX);
                $textSvg->addAttribute('y', $textY);
                $textSvg->addAttribute('width', $this->width);
                $textSvg->addAttribute('height', $lineHeight);

                $textSvg->addAttribute('font-size', $fontSize);

                if (str_starts_with(trim($this->fillColor), '#')) {
                    [$fill, $fillOpacity] = $this->hex8ToHex6AndOpacity($this->fillColor);
                } else {
                    $fill = $this->fillColor;
                    $fillOpacity = 1;
                }
                $textSvg->addAttribute('fill', $fill);
                $textSvg->addAttribute('fill-opacity', $fillOpacity);

                if (str_starts_with(trim($this->strokeColor), '#')) {
                    [$stroke, $strokeOpacity] = $this->hex8ToHex6AndOpacity($this->strokeColor);
                } else {
                    $stroke = $this->strokeColor;
                    $strokeOpacity = 1;
                }
                $textSvg->addAttribute('stroke', $stroke);
                $textSvg->addAttribute('stroke-opacity', $strokeOpacity);

                $textSvg->addAttribute('stroke-width', "{$this->strokeWidth}px");
                $textSvg->addAttribute('font-family', "{$this->fontFamily}, Times New Roman");
                $textSvg->addAttribute('text-align', $this->textAlign);

                if ($this->textAnchor) {
                    $textSvg->addAttribute('text-anchor', $this->textAnchor);
                }

                $svg[] = $textSvg;
            }
        }

        return $svg;
    }
}
