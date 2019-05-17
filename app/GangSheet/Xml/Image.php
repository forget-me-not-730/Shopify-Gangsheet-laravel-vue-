<?php

namespace App\GangSheet\Xml;

use App\GangSheet\Abstracts\Image as AbstractImage;
use App\Extensions\Image\Facades\Image as AppImage;
use App\Extensions\Image\Image as iImage;
use App\GangSheet\Classes\Svg;
use App\GangSheet\Exceptions\GenerationException;
use \DOMDocument;

class Image extends AbstractImage
{

    protected iImage|null $image = null;

    public function getSourceId(): string
    {
        return hash('sha256', json_encode([
            'src' => $this->src,
        ]));
    }

    public function getImageId(): string
    {
        $transforms = $this->getTransforms();

        if (count($transforms) > 0) {
            return hash('sha256', json_encode([
                'key' => $this->getSourceId(),
                'transforms' => $transforms,
            ]));
        } else {
            return $this->getSourceId();
        }
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

    function replaceDefsHrefReferences($xpath, $id, $localName): void
    {
        foreach ($xpath->query('//*[local-name()="' . $localName . '"]') as $element) {
            try {
                $oldId = $element->getAttribute('id');
                $oldHref = $element->getAttribute('href');

                if ($localName == 'use' && $oldHref) {
                    $oldHref = str_replace('#', '', $oldHref);
                    $element->setAttribute('href', "#$id-$oldHref");
                }

                if ($oldId) {
                    $newId = "$id-$oldId";
                    $element->setAttribute('id', $newId);

                    foreach ($xpath->query('//*[@xlink:href="#' . rawurlencode($oldId) . '"]') as $referencingElement) {
                        try {
                            $referencingElement->setAttribute('xlink:href', '#' . $newId);
                        } catch (\Exception $exc) {
                            // Ignore exception
                        }
                    }
                }
            } catch (\Exception $exception) {
                // Ignore exception
            }
        }
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


    function makeClipPathUnique($svgContent, $id): string
    {
        $domDocument = new DOMDocument();
        $domDocument->loadXML($svgContent, LIBXML_PARSEHUGE);

        $xpath = new \DOMXPath($domDocument);

        $this->replaceDefsUrlReferences($xpath, $id, 'clipPath', 'clip-path');
        $this->replaceDefsUrlReferences($xpath, $id, 'mask', 'mask');
        $this->replaceDefsUrlReferences($xpath, $id, 'linearGradient', 'fill');
        $this->replaceDefsUrlReferences($xpath, $id, 'radialGradient', 'fill');
        $this->replaceDefsUrlReferences($xpath, $id, 'pattern', 'fill');

        $this->replaceDefsHrefReferences($xpath, $id, 'image');
        $this->replaceDefsHrefReferences($xpath, $id, 'use');
        $this->replaceDefsHrefReferences($xpath, $id, 'symbol');
        $this->replaceDefsHrefReferences($xpath, $id, 'g');

        return $domDocument->saveXML();
    }

    public function toImage(): iImage
    {
        if (!$this->image) {
            $this->image = AppImage::make($this->getSrc());

            if (count($this->filters) > 0) {
                $this->image->applyFilters($this->filters);
            }
        }

        return $this->image;
    }

    public function encode($format = 'data-url'): string
    {
        $image = $this->toImage();

        return $image->encode($format)->encoded;
    }

    /**
     * @throws GenerationException
     */
    public function toSvg(): Svg
    {
        $imageId = $this->getImageId();

        if ($this->isPng()) {
            $svg = new Svg("<image></image>");

            $image = $this->toImage();

            // Resize image if it's too big to reduce the size of the Gang Sheet File
            $resizeWH = max($this->width * 2, $this->height * 2);
            if ($resizeWH < $image->width() && $resizeWH < $image->height()) {
                $image->resize($resizeWH, $resizeWH, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $svg->addAttribute('id', $imageId);
            $svg->addAttribute('href', $this->encode());

            $svg->addAttribute('width', $image->width());
            $svg->addAttribute('height', $image->height());

            if ($this->opacity) {
                $opacity = max(0, min(1, floatval($this->opacity)));
                $svg->addAttribute('opacity', $opacity);
            }
        } else {
            $svgContent = file_get_contents($this->getSrc());

            // Remove XML declaration if present
            $svgContent = preg_replace('/<\?xml.*\?>/', '', $svgContent);

            // Remove DOCTYPE if present
            $svgContent = preg_replace('/<!DOCTYPE.*?>/', '', $svgContent);

            $svgContent = $this->makeClassNamesUnique($svgContent, $imageId);
            $svgContent = $this->makeClipPathUnique($svgContent, $imageId);

            $fileSvg = Svg::loadFromContent($svgContent);

            if (!empty($fileSvg['error'])) {
                throw new GenerationException($fileSvg['error']);
            }

            $fileSvg->addViewBoxClipPath();

            unset($fileSvg['xmlns']);

            $svg = new Svg("<symbol></symbol>");
            $svg->addAttribute('id', $imageId);
            $svg->addAttribute('width', $fileSvg['width']);
            $svg->addAttribute('height', $fileSvg['height']);

            $svg->appendChild($fileSvg);
        }

        return $svg;
    }
}
