<?php

namespace App\Services;

use App\Enums\MimeType;
use App\GangSheet\Imagick\Text;
use App\Models\Design;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as Image;
use Symfony\Component\Process\Process;

class SvgService
{
    public string $design_id;

    public float $width;

    public float $height;

    public static function load(string $svgFile): bool|\SimpleXMLElement
    {
        if (is_file($svgFile)) {
            $svgContent = file_get_contents($svgFile);
        }

        if (str_starts_with($svgFile, 'http')) {
            $svgContent = file_get_contents($svgFile);
        }

        if (file_exists($svgFile)) {
            $svgContent = file_get_contents($svgFile);
        }

        if (!isset($svgContent)) {
            $svgContent = $svgFile;
        }

        // Remove Icc-color from style attribute
        $pattern = '/style="fill:#([0-9a-fA-F]{6})(?:\s+icc-color\([^)]*\))?/';
        $replacement = 'style="fill:#$1';
        $svgContent = preg_replace($pattern, $replacement, $svgContent);

        return simplexml_load_string($svgContent, \SimpleXMLElement::class, LIBXML_PARSEHUGE);
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

    public static function removeElements(\SimpleXMLElement $xml, $tagName): void
    {
        if (property_exists($xml, $tagName)) {
            unset($xml->$tagName);
        }

        // Recursively remove the specified tag from all child elements
        foreach ($xml->children() as $child) {
            self::removeElements($child, $tagName);
        }
    }

    public static function isValid($xml): array|\SimpleXMLElement
    {
        try {
            if (!($xml instanceof \SimpleXMLElement)) {
                $xml = self::load($xml);
            }

            if (is_bool($xml)) {
                return [
                    'error' => true,
                    'message' => 'Failed to load SVG file'
                ];
            }

            $viewBox = $xml['viewBox'] ?? null;

            if (empty($viewBox)) {
                return [
                    'error' => true,
                    'message' => 'SVG file does not have a viewBox attribute'
                ];
            }

            $viewBox = explode(' ', $viewBox);

            if (!($xml['width'] ?? null)) {
                $xml['width'] = $viewBox[2] - $viewBox[0];
            }

            if (!($xml['height'] ?? null)) {
                $xml['height'] = $viewBox[3] - $viewBox[1];
            }

            $xml['width'] = self::getPixelValue($xml['width']);
            $xml['height'] = self::getPixelValue($xml['height']);

            unset($xml['x']);
            unset($xml['y']);

            // Register SVG namespace to handle SVG files correctly
            $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

            // Find elements that have a "font-family" style or attribute
            $elementsWithFontFamily = $xml->xpath('//*[@style[contains(., "font-family")]] | //*[@font-family]');

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
                        'error' => true,
                        'message' => "Font '$fontName' is not installed"
                    ];
                }
            }

            // Remove invalid tags/elements to process
            self::removeElements($xml, 'metadata');
            self::removeElements($xml, 'foreignObject');
            self::removeElements($xml, 'i:aipgf');
            self::removeElements($xml, 'i:aipgfRef');

            return $xml;
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage()
            ];
        }
    }

    public function getCoords($object): array
    {
        $x = $object['x'] - $object['width'] / 2 + $this->width / 2;
        $y = $object['y'] - $object['height'] / 2 + $this->height / 2;
        $centerX = $x + $object['width'] / 2;
        $centerY = $y + $object['height'] / 2;

        return [$x, $y, $object['width'], $object['height'], $centerX, $centerY, $object['angle'] ?? 0];
    }

    public function setCoords(\SimpleXMLElement $use, \SimpleXMLElement $svg, $object): void
    {
        [$x, $y, $width, $height, $centerX, $centerY, $angle] = $this->getCoords($object);

        $scaleX = $width / $svg['width'];
        $scaleY = $height / $svg['height'];

        $x /= $scaleX;
        $y /= $scaleY;

        $use->addAttribute('x', $x);
        $use->addAttribute('y', $y);

        $use->addAttribute('transform', "rotate($angle, $centerX, $centerY) scale($scaleX, $scaleY)");
    }

    public function getDefs(\SimpleXMLElement $svg): \SimpleXMLElement
    {
        if (!isset($svg->defs)) {
            $defs = $svg->addChild('defs');
        } else {
            $defs = $svg->defs;
        }

        return $defs;
    }

    public function addImage(\SimpleXMLElement $svg, $object): \SimpleXMLElement
    {
        $fileName = basename($object['src']);
        $imageId = explode('.', $fileName)[0];

        if ($object['filters'] ?? null) {
            $imageId .= '_' . hash('sha256', json_encode($object['filters']));
        }

        $defs = $this->getDefs($svg);

        foreach ($defs->children() as $child) {
            if ($child->getName() === 'image' && $child['id'] == $imageId) {
                return $child;
            }
        }

        $im = Image::make($object['src']);

        if ($object['filters'] ?? null) {
            $im->applyFilters($object['filters']);
        }

        $image = $defs->addChild('image');
        $image->addAttribute('id', $imageId);
        $image->addAttribute('href', $im->encode('data-url')->encoded);

        $image->addAttribute('width', $im->width());
        $image->addAttribute('height', $im->height());

        return $image;
    }

    public function makeClassNamesUnique($svgContent, $id): string
    {
        $pattern = '/<style.*?>(.*?)<\/style>/s';

        // Callback function to prepend prefix to each class name
        $replaceCallback = function ($matches) use ($id) {
            $styleContent = $matches[1];

            // Regex to find all class names in the style content
            $classPattern = '/(\.[^{}]+)\s*\{/';
            $classPrefix = '#' . $id;

            $styleContent = preg_replace_callback($classPattern, function ($classMatches) use ($classPrefix) {
                $className = $classMatches[1];

                if (str_contains($className, ',')) {
                    $classes = explode(',', $className);
                    $classes = array_map(function ($class) use ($classPrefix) {
                        return "$classPrefix $class";
                    }, $classes);

                    return implode(', ', $classes) . ' {';
                } else {
                    return "$classPrefix $className {"; // Prepend prefix to class name
                }
            }, $styleContent);
            // replace fill and stroke attributes

            $styleContent = preg_replace('/fill:url\(#([^)]+)\)/', 'fill:url(#' . $id . '-$1)', $styleContent);
            $styleContent = preg_replace('/stroke:url\(#([^)]+)\)/', 'stroke:url(#' . $id . '-$1)', $styleContent);

            return "<style>$styleContent</style>"; // Return updated style tag
        };

        // Apply the callback to modify style tag
        return preg_replace_callback($pattern, $replaceCallback, $svgContent);
    }

    function replaceDefsUrlReferences($xpath, $id, $localName, $attributeName): void
    {
        foreach ($xpath->query('//*[local-name()="' . $localName . '"]') as $element) {
            $oldId = $element->getAttribute('id');

            if ($oldId) {
                try {
                    $newId = "$id-$oldId";
                    $element->setAttribute('id', $newId);

                    foreach ($xpath->query('//*[@' . $attributeName . '="url(#' . rawurlencode($oldId) . ')"]') as $referencingElement) {
                        $referencingElement->setAttribute($attributeName, 'url(#' . $newId . ')');
                    }

                    // Some elements have fill property with style attribute
                    foreach ($xpath->query('//*[@style]') as $referencingElement) {
                        $style = $referencingElement->getAttribute('style');
                        if ($style) {
                            // remove spaces from style string
                            $style = preg_replace('/\s+/', '', $style);
                            if (str_contains($style, $attributeName . ':url(#' . $oldId . ')')) {
                                $style = str_replace($attributeName . ':url(#' . $oldId . ')', $attributeName . ':url(#' . $newId . ')', $style);
                                $referencingElement->setAttribute('style', $style);
                            }
                        }
                    }

                    foreach ($xpath->query('//*[@xlink:href="#' . rawurlencode($oldId) . '"]') as $referencingElement) {
                        $referencingElement->setAttribute('xlink:href', '#' . $newId);
                    }
                } catch (\Exception $exception) {
                    // Ignore exception
                }
            }
        }
    }

    function replaceDefsHrefReferences($xpath, $id, $localName): void
    {
        foreach ($xpath->query('//*[local-name()="' . $localName . '"]') as $element) {
            $oldId = $element->getAttribute('id');

            if ($oldId) {
                try {
                    $newId = "$id-$oldId";
                    $element->setAttribute('id', $newId);

                    foreach ($xpath->query('//*[@xlink:href="#' . rawurlencode($oldId) . '"]') as $referencingElement) {
                        $referencingElement->setAttribute('xlink:href', '#' . $newId);
                    }
                } catch (\Exception $exception) {
                    // Ignore exception
                }
            }
        }
    }

    function makeClipPathUnique($svgContent, $id): string
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML($svgContent, LIBXML_PARSEHUGE);

        $xpath = new \DOMXPath($domDocument);

        $this->replaceDefsUrlReferences($xpath, $id, 'clipPath', 'clip-path');
        $this->replaceDefsUrlReferences($xpath, $id, 'mask', 'mask');
        $this->replaceDefsUrlReferences($xpath, $id, 'linearGradient', 'fill');
        $this->replaceDefsUrlReferences($xpath, $id, 'radialGradient', 'fill');
        $this->replaceDefsUrlReferences($xpath, $id, 'pattern', 'fill');

        $this->replaceDefsHrefReferences($xpath, $id, 'image');
        $this->replaceDefsHrefReferences($xpath, $id, 'symbol');
        $this->replaceDefsHrefReferences($xpath, $id, 'g');

        return $domDocument->saveXML();
    }

    public function addSVG(\SimpleXMLElement $svg, $svgUrl): \SimpleXMLElement
    {
        $fileName = basename($svgUrl);
        $svgId = explode('.', $fileName)[0];

        $defs = $this->getDefs($svg);

        foreach ($defs->children() as $child) {
            if ($child->getName() === 'symbol' && $child['id'] == $svgId) {
                return $child;
            }
        }

        // Invalidate SVG cache
        $svgUrl .= '?' . time();

        $svgContent = file_get_contents($svgUrl);

        // Remove XML declaration if present
        $svgContent = preg_replace('/<\?xml.*\?>/', '', $svgContent);

        // Remove DOCTYPE if present
        $svgContent = preg_replace('/<!DOCTYPE.*?>/', '', $svgContent);

        $svgContent = $this->makeClassNamesUnique($svgContent, $svgId);
        $svgContent = $this->makeClipPathUnique($svgContent, $svgId);

        $fileSvg = simplexml_load_string($svgContent, \SimpleXMLElement::class, LIBXML_PARSEHUGE);

        $this->addViewBoxClipPath($fileSvg);

        $symbol = $defs->addChild('symbol');
        $symbol->addAttribute('id', $svgId);
        $symbol->addAttribute('width', $fileSvg['width']);
        $symbol->addAttribute('height', $fileSvg['height']);

        $node = dom_import_simplexml($symbol);
        $domChild = dom_import_simplexml($fileSvg);

        $node->appendChild($node->ownerDocument->importNode($domChild, true));

        return $symbol;
    }

    public function isElementOutOfViewBox($matrix, $viewBox): bool
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
            list($viewBoxMinX, $viewBoxMinY, $viewBoxWidth, $viewBoxHeight) = explode(' ', $viewBox);

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

    public function addViewBoxClipPath(\SimpleXMLElement $svg): void
    {
        $viewBox = $svg['viewBox'] ?? null;
        if (empty($viewBox)) {
            $viewBox = "0 0 {$svg['width']} {$svg['height']}";
        }
        [$x, $y, $width, $height] = explode(' ', $viewBox);
        $defs = $this->getDefs($svg);
        $clipPath = $defs->addChild('clipPath');
        $clipPathId = 'clip-path_' . Str::uuid()->toString();
        $clipPath->addAttribute('id', $clipPathId);
        $polygon = $clipPath->addChild('polygon');

        $x1 = intval($x) - 1;
        $y1 = intval($y) - 1;
        $x2 = $x1 + intval($width) + 2;
        $y2 = $y1 + intval($height) + 2;

        $points = "$x $y, $x2 $y1, $x2 $y2, $x1 $y2";
        $polygon->addAttribute('points', $points);

        // add clip-path to all child elements except <defs>, but should not override the existing clip-path
        foreach ($svg->children() as $child) {
            if (!empty($child['transform'])) {
                $transform = $child['transform'];

                if ($this->isElementOutOfViewBox($transform, $viewBox)) {
                    // remove child element
                    $dom = dom_import_simplexml($child);
                    $dom->parentNode?->removeChild($dom);
                }
                continue;
            }

            if ($child->getName() !== 'defs' && empty($child['clip-path']) && empty($child['mask'])) {
                $child->addAttribute('clip-path', "url(#$clipPathId)");
            }
        }
    }

    public function embedSvg(\SimpleXMLElement $svg, $object): bool
    {
        try {
            $symbol = $this->addSVG($svg, $object['src']);

            $svgUse = $svg->addChild('use');
            $svgUse->addAttribute('href', "#{$symbol['id']}");

            $this->setCoords($svgUse, $symbol, $object);

            return true;
        } catch (\Exception $exception) {
            report($exception);

            return false;
        }
    }

    public function embedPng(\SimpleXMLElement $svg, $object): bool
    {
        try {
            $image = $this->addImage($svg, $object);

            $useImage = $svg->addChild('use');
            $useImage->addAttribute('href', "#{$image['id']}");

            $this->setCoords($useImage, $image, $object);
            $useImage->addAttribute('preserveAspectRatio', 'none');

            return true;
        } catch (\Exception $exception) {
            report($exception);

            return false;
        }
    }

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


    public function embedText(\SimpleXMLElement $svg, $object): bool
    {
        try {
            [$x, $y, $width, $height, $centerX, $centerY, $angle] = $this->getCoords($object);
            $fontFamily = $object['fontFamily'] ?? 'Arial';
            $fillColor = strtolower($object['fillColor'] ?? '#000000ff');
            $strokeColor = strtolower($object['strokeColor'] ?? '#00000000');
            $strokeWidth = intval($object['strokeWidth'] * $object['scaleY']);
            $fontSize = intval($object['fontSize'] * $object['scaleY']);

            // Ensure front installed
            FontService::installFont($fontFamily);

            $textLines = preg_split('/\r?\n/', $object['text']);
            $lineCount = count($textLines);
            $lineHeight = intval($height / $lineCount);

            foreach ($textLines as $lineNumber => $lineText) {
                if (trim($lineText)) {
                    $offsetX = 0;
                    if (!empty($object['leftLines'][$lineNumber])) {
                        $offsetX = intval($object['leftLines'][$lineNumber]);
                    }

                    if (empty($object['baseLines'][$lineNumber])) {
                        $measurements = Text::create($object)->getMeasurements();
                        $ascenderRatio = ($measurements['ascender'] - $strokeWidth * 2) / $measurements['textHeight'];
                        $offsetY = intval($lineHeight * $ascenderRatio);
                    } else {
                        $offsetY = intval($object['baseLines'][$lineNumber] + $object['height'] / 2);
                    }

                    $textX = $x + $offsetX;
                    $textY = $y + $offsetY;

                    $lineText = htmlspecialchars("$lineText", ENT_QUOTES, 'UTF-8');
                    $textSvg = new \SimpleXMLElement("<text>$lineText</text>");
                    $textSvg->addAttribute('x', $textX);
                    $textSvg->addAttribute('y', $textY);
                    $textSvg->addAttribute('width', $width);
                    $textSvg->addAttribute('height', $height);

                    $textSvg->addAttribute('font-size', $fontSize);

                    $transformations = [];

                    if ($angle != 0) {
                        $transformations[] = "rotate($angle $centerX $centerY)";
                    }

                    $transform = implode(' ', $transformations);
                    $textSvg->addAttribute('transform', $transform);

                    if (str_starts_with(trim($fillColor), '#')) {
                        [$fill, $fillOpacity] = $this->hex8ToHex6AndOpacity($fillColor);
                    } else {
                        $fill = $fillColor;
                        $fillOpacity = 1;
                    }
                    $textSvg->addAttribute('fill', $fill);
                    $textSvg->addAttribute('fill-opacity', $fillOpacity);

                    if (str_starts_with(trim($strokeColor), '#')) {
                        [$stroke, $strokeOpacity] = $this->hex8ToHex6AndOpacity($strokeColor);
                    } else {
                        $stroke = $strokeColor;
                        $strokeOpacity = 1;
                    }
                    $textSvg->addAttribute('stroke', $stroke);
                    $textSvg->addAttribute('stroke-opacity', $strokeOpacity);

                    $textSvg->addAttribute('stroke-width', "{$strokeWidth}px");
                    $textSvg->addAttribute('font-family', "$fontFamily, Times New Roman");

                    $node = dom_import_simplexml($svg);
                    $domTextSvg = dom_import_simplexml($textSvg);

                    $node->appendChild($node->ownerDocument->importNode($domTextSvg, true));
                }
            }

            return true;
        } catch (\Exception $exception) {
            report($exception);

            return false;
        }
    }

    public function addWatermark(\SimpleXMLElement $svg): void
    {
        $watermarkText = 'Build A Gang Sheet';
        $fontSize = min($this->width, $this->height) / strlen($watermarkText) * 2;
        $watermarkText = $svg->addChild('text', $watermarkText);
        $watermarkText->addAttribute('x', $this->width / 2);
        $watermarkText->addAttribute('y', $this->height / 2);
        $watermarkText->addAttribute('fill', '#ffffff');
        $watermarkText->addAttribute('font-size', $fontSize);
        $watermarkText->addAttribute('font-family', 'Oswald');
        $watermarkText->addAttribute('text-anchor', 'middle');
        $watermarkText->addAttribute('dominant-baseline', 'middle');
        $watermarkText->addAttribute('transform', 'rotate(-45, ' . ($this->width / 2) . ', ' . ($this->height / 2) . ')');
    }

    public function setFileNameStyle(\SimpleXMLElement $text): void
    {
        $text->addAttribute('fill', '#000000');
        $text->addAttribute('fill-opacity', 1);
        $text->addAttribute('text-anchor', 'middle');
        $text->addAttribute('font-size', 48);
        $text->addAttribute('font-family', 'Oswald');
    }

    public function embedFontCss(\SimpleXMLElement $svg, $user_id): void
    {
        // Add Font CSS
        $fontCssUrl = spaces()->url('fonts/css/default.css');

        $userCssPath = 'fonts/css/' . $user_id . '.css';
        if (spaces()->exists($userCssPath)) {
            $fontCssUrl = spaces()->url($userCssPath);
        }

        $link = $svg->addChild('link', '');
        $link->addAttribute('href', $fontCssUrl);
        $link->addAttribute('type', 'text/css');
        $link->addAttribute('rel', 'stylesheet');
        $link->addAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
    }

    public function waitForFile($filePath, $timeout = 120): bool
    {
        $start = time();
        while (!file_exists($filePath)) {
            if (time() - $start > $timeout) {
                return false;
            }
            sleep(1);
        }

        return true;
    }

    public function generateGangSheet($design_id): bool
    {
        $this->design_id = $design_id;
        $design = Design::withTrashed()->find($design_id);

        $design->update(['status' => Design::STATUS_PROCESSING]);

        if (!storage()->exists($design_id)) {
            storage()->makeDirectory($design_id);
        }

        $success = true;

        try {
            $json = $design->getDesignJson(false);
            $this->width = $json['width'];
            $this->height = $json['height'];
            $actualHeight = $this->height;

            $autoTrimGangSheet = $design->user->getSetting('autoTrimGangSheet', false);
            if ($autoTrimGangSheet) {
                $actualHeight = $design->getGangSheetHeight();
            }

            $svg = new \SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

            // Set the width and height of the SVG
            $svg->addAttribute('width', $this->width);
            $svg->addAttribute('height', $this->height);

            // $this->embedFontCss($svg, $design->user_id);

            // Embed objects
            foreach ($json['objects'] as $object) {
                if ($object['type'] === 'image') {
                    if (($object['mimeType'] ?? null) == MimeType::SVG->value || str_ends_with($object['src'], '.svg')) {
                        $success = $this->embedSvg($svg, $object);
                    } else {
                        $success = $this->embedPng($svg, $object);
                    }
                } elseif ($object['type'] === 'text') {
                    $success = $this->embedText($svg, $object);
                }

                if (!$success) {
                    $design->update(['status' => Design::STATUS_FAILED]);
                    return false;
                }
            }

            $svgPath = storage()->path($design_id . '/gang_sheet.svg');

            // Calculate the final width and height of the SVG
            $finalWidth = 96 * $this->width / 300;
            $actualHeightSVG = 96 * $actualHeight / 300;

            // Set the viewBox attribute
            $svg['viewBox'] = "0 0 {$this->width} {$actualHeight}";

            $svg['width'] = $finalWidth;
            $svg['height'] = $actualHeightSVG;

            $printFileName = $design->getSetting('printFileName', false);

            if ($printFileName) {
                $printFileNamePosition = $design->getSetting('printFileNamePosition', 'top');
                $fileNameHeightSetting = intval($design->getSetting('printFileNameHeight', 1) * 96);

                $fontSize = intval($fileNameHeightSetting / 2);

                if ($printFileNamePosition === 'both') {
                    $fileNameHeight = $fileNameHeightSetting * 2;
                } else {
                    $fileNameHeight = $fileNameHeightSetting;
                }

                $svgWithFileName = new \SimpleXMLElement('<svg xmlns="http://www.w3.org/2000/svg"></svg>');

                $svgWithFileName->addAttribute('width', $finalWidth);
                $svgWithFileName->addAttribute('height', $actualHeightSVG + $fileNameHeight);
                $svgWithFileName->addAttribute('viewBox', "0 0 $finalWidth " . ($actualHeightSVG + $fileNameHeight));

                FontService::installFont('Oswald');

                $fileName = $design->getGangSheetFileName();

                $fileName = str_replace('&nbsp;', ' ', $fileName);
                $fileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');

                // Get max font size based on the length of the file name
                $maxFileNameWidth = 0.9 * $finalWidth;
                $lengthOfFileName = strlen($fileName);
                $maxFontSize = intval($maxFileNameWidth / $lengthOfFileName * 2.5);

                $fontSize = min($fontSize, $maxFontSize);

                $textSvg = new \SimpleXMLElement("<text>{$fileName}</text>");
                $textSvg->addAttribute('x', $finalWidth / 2);
                $textSvg->addAttribute('fill', '#000000');
                $textSvg->addAttribute('fill-opacity', 1);
                $textSvg->addAttribute('text-anchor', 'middle');
                $textSvg->addAttribute('font-size', $fontSize);
                $textSvg->addAttribute('font-family', 'Oswald');

                $topTextSvg = clone $textSvg;
                $bottomTextSvg = clone $textSvg;

                $textOffsetTop = intval($fileNameHeightSetting / 2) + $fontSize / 3;

                $topTextSvg->addAttribute('y', $textOffsetTop);
                if ($printFileNamePosition === 'both') {
                    $bottomTextSvg->addAttribute('y', $actualHeightSVG + $fileNameHeightSetting + $textOffsetTop);
                } else {
                    $bottomTextSvg->addAttribute('y', $actualHeightSVG + $textOffsetTop);
                }

                $svg['x'] = 0;
                if ($printFileNamePosition === 'bottom') {
                    $svg['y'] = 0;
                } else {
                    $svg['y'] = $fileNameHeightSetting;
                }

                // add clip path to svg
                $clipPath = $svgWithFileName->addChild('clipPath');
                $clipPath->addAttribute('id', 'gang-sheet-clip-path');
                $polygon = $clipPath->addChild('polygon');

                if ($printFileNamePosition === 'top' || $printFileNamePosition === 'both') {
                    $points = "0 $fileNameHeightSetting, $finalWidth $fileNameHeightSetting, $finalWidth " . ($actualHeightSVG + $fileNameHeightSetting) . ", 0 " . ($actualHeightSVG + $fileNameHeightSetting);
                } else {
                    $points = "0 0, $finalWidth 0, $finalWidth $actualHeightSVG, 0 $actualHeightSVG";
                }

                $polygon->addAttribute('points', $points);
                $svg->addAttribute('clip-path', 'url(#gang-sheet-clip-path)');

                $node = dom_import_simplexml($svgWithFileName);
                $domTopTextSvg = dom_import_simplexml($topTextSvg);
                $domBottomTextSvg = dom_import_simplexml($bottomTextSvg);
                $domeSvg = dom_import_simplexml($svg);

                if ($printFileNamePosition === 'top' || $printFileNamePosition === 'both') {
                    $node->appendChild($node->ownerDocument->importNode($domTopTextSvg, true));
                }

                if ($printFileNamePosition === 'bottom' || $printFileNamePosition === 'both') {
                    $node->appendChild($node->ownerDocument->importNode($domBottomTextSvg, true));
                }

                $node->appendChild($node->ownerDocument->importNode($domeSvg, true));

                $svgWithFileName->asXML($svgPath);

                $design->setMetaData('has_file_name', $printFileNamePosition);
            } else {
                $svg->asXML($svgPath);
            }

            // Add Watermark
            $watermarkPath = storage()->path($design_id . '/watermark.svg');
            $this->addWatermark($svg);
            $svg->asXML($watermarkPath);

            if ($design->hasText()) {
                $process = Process::fromShellCommandline("inkscape $svgPath -o $svgPath -T", timeout: 600);
                $process->run();
            }

            $gangSheetFileExtension = $design->user->getSetting('gangSheetFileExtension', '.png');
            $outputFilePath = storage()->path($design_id . '/gang_sheet' . $gangSheetFileExtension);

            $command = "inkscape $svgPath -o $outputFilePath -d 300";

            $process = Process::fromShellCommandline($command, timeout: 600);
            $process->run();

            if ($process->isSuccessful()) {

                if (!$this->waitForFile($outputFilePath)) {
                    throw new \Exception('Failed to generate gang sheet for design: ' . $design_id);
                }

                $visibility = 'private';

                if ($design->user->isCustomStore()) {
                    $visibility = 'public';
                }

                $content = file_get_contents($outputFilePath);
                spaces()->put($design->image_path, $content, ['visibility' => $visibility]);

                // Generate Watermark File
                $watermarkOutputFilePath = storage()->path($design_id . '/watermark' . $gangSheetFileExtension);
                $command = "inkscape $watermarkPath -o $watermarkOutputFilePath -d 300";
                $process = Process::fromShellCommandline($command, timeout: 300);
                $process->run();

                if ($process->isSuccessful()) {
                    if (!$this->waitForFile($watermarkOutputFilePath)) {
                        throw new \Exception('Failed to generate watermark image for design: ' . $design_id);
                    }

                    $content = file_get_contents($watermarkOutputFilePath);
                    spaces()->put($design->watermark_path, $content);
                }

                $design->update(['status' => Design::STATUS_COMPLETED]);

            } else {
                info($process->getErrorOutput());
                $design->update(['status' => Design::STATUS_FAILED]);

                $success = false;
            }

        } catch (\Exception $exception) {
            info($exception);

            $design->update(['status' => Design::STATUS_FAILED]);
            $success = false;
        } finally {
            storage()->deleteDirectory($design_id);
        }

        return $success;
    }

    public static function trimSVG($filePath): array|\SimpleXMLElement
    {
        $command = "inkscape {$filePath} --export-area-drawing -l --export-overwrite";
        $process = Process::fromShellCommandline($command, timeout: 60);
        $process->run();

        return self::isValid($filePath);
    }
}
