<?php

namespace App\GangSheet\Classes;

use App\Services\FontService;
use \SimpleXMLElement;
use \DOMElement;

class Svg extends SimpleXMLElement
{
    static public function loadFromFile(string $filename): array|static
    {
        $content = file_get_contents($filename);

        return self::loadFromContent($content);
    }

    static public function loadFromContent(string $content): array|static
    {
        // Remove Icc-color from style attribute
        $pattern = '/style="fill:#([0-9a-fA-F]{6})(?:\s+icc-color\([^)]*\))?/';
        $replacement = 'style="fill:#$1';
        $svgContent = preg_replace($pattern, $replacement, $content);

        $svg = simplexml_load_string($svgContent, self::class, LIBXML_PARSEHUGE);

        if ($svg === false) {
            return [
                'error' => 'Invalid SVG content',
            ];
        }

        $width = $svg->getWidth();
        $height = $svg->getHeight();

        $viewBox = $svg->getViewBox();

        if ((empty($width) || empty($height)) && empty($viewBox)) {
            return [
                'error' => 'Invalid SVG dimensions',
            ];
        }

        if (empty($width)) {
            $width = $viewBox[2];
        }

        if (empty($height)) {
            $height = $viewBox[3];
        }

        unset($svg['x']);
        unset($svg['y']);

        $svg['width'] = (string)$width;
        $svg['height'] = (string)$height;
        $svg['preserveAspectRatio'] = 'none';

        // Register the SVG namespace
        $svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');


        // Find elements that have a "font-family" style or attribute
        $elementsWithFontFamily = $svg->xpath('//*[@style[contains(., "font-family")]] | //*[@font-family]');

        $fontFamilies = [];

        foreach ($elementsWithFontFamily as $element) {
            // Extract font-family from style attribute
            if (!empty($element['style'])) {
                preg_match('/font-family:([^;]+);/', (string)$element['style'], $matches);
                if (!empty($matches[1])) {
                    $fontFamilies[] = trim($matches[1]);
                }
            }

            // Direct font-family attribute
            if (!empty($element['font-family'])) {
                $fontFamilies[] = trim((string)$element['font-family']);
            }
        }

        if (!empty($xml->style)) {
            if (preg_match_all('/font-family:([^;]+)/i', $xml->style, $matches)) {
                foreach ($matches[1] as $fontFamily) {
                    $fontFamilies[] = trim($fontFamily);
                }
            }
        }

        $style = $xml->defs->style ?? $xml->style ?? null;
        if ($style) {
            $styleContent = (string)$style;
            if (preg_match_all('/font-family:([^;]+)/i', $styleContent, $matches)) {
                foreach ($matches[1] as $fontFamily) {
                    foreach (explode(',', trim($fontFamily)) as $fontName) {
                        if (trim($fontName)) {
                            $fontFamilies[] = trim(trim($fontName), "'");
                        }
                    }
                }
            }
        }

        // Remove duplicates and print the list of font families
        $fontFamilies = array_unique($fontFamilies);

        foreach ($fontFamilies as $fontName) {
            if (!FontService::isFontInstalled($fontName)) {
                return [
                    'error' => "Font '$fontName' is not installed",
                ];
            }
        }

        // Remove unwanted elements
        $svg->removeElements([
            'metadata',
            'foreignObject',
            'i:aipgf',
            'i:aipgfRef',
            'i:pgf',
        ]);

        return $svg;
    }

    static public function create(int $width, int $height): static
    {
        $svg = new static('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $svg->addAttribute('width', $width);
        $svg->addAttribute('height', $height);
        $svg->addAttribute('viewBox', "0 0 $width $height");

        return $svg;
    }

    public static function getPixelValue(string $size): int
    {
        $map = [
            'px' => 1,
            'em' => 16,
            'ex' => 16 / 2,
            'pt' => 16 / 12,
            'pc' => 16,
            'in' => 16 * 6,
            'cm' => 16 / (2.54 / 6),
            'mm' => 16 / (25.4 / 6),
        ];

        $size = trim($size);

        $value = substr($size, 0, -2);
        $unit = substr($size, -2);

        if (is_numeric($value) && isset($map[$unit])) {
            $pixelValue = ((float)$value) * $map[$unit];
        } elseif (is_numeric($size)) {
            $pixelValue = (float)$size;
        } else {
            $pixelValue = 0;
        }

        return (int)round($pixelValue);
    }

    public function appendChild(DOMElement|Svg $child): void
    {
        $node = dom_import_simplexml($this);

        if ($child instanceof Svg) {
            $child = dom_import_simplexml($child);
        }

        $node->appendChild($node->ownerDocument->importNode($child, true));
    }

    public function getDefs(): Svg
    {
        if (!isset($this->defs)) {
            $defs = $this->addChild('defs');
        } else {
            $defs = $this->defs;
        }

        return $defs;
    }

    public function getViewBox(): array
    {
        $viewBox = $this['viewBox'] ?? null;
        if (empty($viewBox)) {
            $this['viewBox'] = "0 0 {$this['width']} {$this['height']}";
            return [0, 0, $this['width'], $this['height']];
        }

        return explode(' ', $viewBox);
    }

    public function isElementOutOfViewBox(string $matrix, array $viewBox): bool
    {
        try {
            // Extract the matrix translation values
            preg_match('/matrix\(([\d\s\.-]+)\)/', $matrix, $matches);
            if (!$matches) {
                return false; // Invalid matrix
            }

            // Split the matrix values
            $matrixValues = explode(' ', $matches[1]);
            if (count($matrixValues) != 6) {
                return false; // Not a proper 6 value matrix
            }

            // Translation values (e, f in the matrix)
            $translateX = floatval($matrixValues[4]);
            $translateY = floatval($matrixValues[5]);

            // Extract viewBox values
            list($viewBoxMinX, $viewBoxMinY, $viewBoxWidth, $viewBoxHeight) = $viewBox;

            // Calculate the viewBox max values
            $viewBoxMaxX = intval($viewBoxMinX) + intval($viewBoxWidth);
            $viewBoxMaxY = intval($viewBoxMinY) + intval($viewBoxHeight);

            // Check if the element is out of bounds
            if ($translateX < $viewBoxMinX || $translateX > $viewBoxMaxX ||
                $translateY < $viewBoxMinY || $translateY > $viewBoxMaxY) {
                return true; // The element is out of the viewBox
            }

            return false; // The element is within the viewBox
        } catch (\Exception $exception) {
            return false;
        }
    }

    public function getId(): ?string
    {
        return $this['id'] ?? null;
    }

    public function addViewBoxClipPath(): void
    {
        $viewBox = $this->getViewBox();
        $defs = $this->getDefs();
        $clipPath = $defs->addChild('clipPath');
        $clipPathId = 'clip-path_view-box-' . $this->getId();
        $clipPath->addAttribute('id', $clipPathId);
        $polygon = $clipPath->addChild('polygon');

        [$x, $y, $width, $height] = $viewBox;
        $x1 = intval($x) - 1;
        $y1 = intval($y) - 1;
        $x2 = $x1 + intval($width) + 2;
        $y2 = $y1 + intval($height) + 2;

        $points = "$x $y, $x2 $y1, $x2 $y2, $x1 $y2";
        $polygon->addAttribute('points', $points);

        // add clip-path to all child elements except <defs>, but should not override the existing clip-path
        // wrap all elements inside group

        $group = $this->addChild('g');
        $group->addAttribute('clip-path', "url(#$clipPathId)");

        foreach ($this->children() as $child) {
            if (in_array($child->getName(), ['defs', 'clipPath', 'mask', 'linearGradient', 'radialGradient', 'pattern', 'symbol', 'style'])) {
                continue;
            }

            $group->appendChild($child);
        }
    }

    public function getWidth(): int
    {
        $width = $this['width'] ?? 0;

        return self::getPixelValue($width);
    }

    public function setWidth(int $width): void
    {
        $this['width'] = (string)$width;

        // update view box
        $viewBox = $this->getViewBox();
        $viewBox[2] = $width;
        $this['viewBox'] = implode(' ', $viewBox);
    }

    public function getHeight(): int
    {
        $height = $this['height'] ?? 0;

        return self::getPixelValue($height);
    }

    public function setHeight(int $height): void
    {
        $this['height'] = (string)$height;

        // update view box
        $viewBox = $this->getViewBox();
        $viewBox[3] = $height;
        $this['viewBox'] = implode(' ', $viewBox);
    }

    public function getX(): int
    {
        return intval($this['x']);
    }

    public function setX(int $x): void
    {
        $this['x'] = $x;
    }

    public function getY(): int
    {
        return intval($this['y']);
    }

    public function setY(int $y): void
    {
        $this['y'] = $y;
    }

    public function setAttribute(string $name, string $value): void
    {
        $this[$name] = $value;
    }

    public function setStroke($strokeWidth, $strokeColor): void
    {
        $this->setAttribute('stroke-width', $strokeWidth);

        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+\.?\d*))?\)/', $strokeColor, $matches)) {
            $stroke = sprintf("#%02x%02x%02x", $matches[1], $matches[2], $matches[3]);
            $strokeOpacity = isset($matches[4]) ? floatval($matches[4]) : 1;
            $this->setAttribute('stroke', $stroke);
            $this->setAttribute('stroke-opacity', $strokeOpacity);
        } else {
            $this->setAttribute('stroke', $strokeColor);
        }
    }

    public function removeElements(string|array $tagNames): void
    {
        if (!is_array($tagNames)) {
            $tagNames = [$tagNames];
        }

        foreach ($tagNames as $tagName) {
            if (property_exists($this, $tagName)) {
                unset($this->$tagName);
            }
        }

        // Recursively remove the specified tag from all child elements
        foreach ($this->children() as $child) {
            $child->removeElements($tagNames);
        }
    }
}
