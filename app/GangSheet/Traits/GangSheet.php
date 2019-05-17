<?php

namespace App\GangSheet\Traits;

use App\GangSheet\Classes\Svg;

trait GangSheet
{
    public function getPixelSize($unit = 'inch'): float
    {
        if ($unit === 'inch' || $unit === 'in') {
            return 300;
        }

        if ($unit === 'mm') {
            return 11.81102362204724;
        }

        if ($unit === 'cm') {
            return 118.1102362204724;
        }

        return 1;
    }

    public function getWidth(): int
    {
        return intval($this->data['meta']['viewport']['width']);
    }

    public function getGangSheetWidth(): int
    {
        return $this->getWidth();
    }

    public function getHeight(): int
    {
        return intval($this->data['meta']['viewport']['height']);
    }

    public function getGangSheetHeight(): int
    {
        return intval($this->getMetaData('actual_height', $this->getHeight()));
    }

    public function getActualHeight(): int
    {
        return $this->getGangSheetHeight();
    }

    public static function getEditUrl($design_id, $token = null): string
    {
        $params = [
            'design_id' => $design_id
        ];

        if ($token) {
            $params['token'] = $token;
        }

        return route('builder.edit', $params);
    }

    public function replaceImageUrls($designData)
    {
        if (!empty($designData['objects'])) {
            foreach ($designData['objects'] as &$object) {
                if ($object['type'] === 'image' && isset($object['src'])) {
                    $src = $this->getImageSrcFromImageObject($object);
                    if ($object['src'] !== $src) {
                        $object['src'] = $src;
                        $object['url'] = $src;
                        $object['required_reload'] = true;
                    }
                }
            }
        } else {
            $designData['objects'] = [];
        }

        return $designData;
    }

    public function fixDesignSvgImages(): void
    {
        $this->addLog("Fixing SVG Images");

        $objects = $this->getDesignJson()['objects'];

        $svgImageUrls = [];

        foreach ($objects as $object) {
            if (isset($object['type']) && $object['type'] === 'image') {
                $src = $object['src'];
                if (str_ends_with($src, '.svg') && !in_array($src, $svgImageUrls)) {
                    $svgImageUrls[] = $src;
                }
            }
        }

        foreach ($svgImageUrls as $url) {
            try {
                $this->addLog("Fixing SVG Image: $url");
                $xml = Svg::loadFromFile($url);

                if (!empty($xml['error'])) {
                    continue;
                }

                $fileName = basename($url);

                if (str_contains($url, 'gallery')) {
                    spaces()->put("gallery/{$this->user_id}/raw/" . $fileName, $xml->asXML());
                } else {
                    spaces()->put('uploads/' . $fileName, $xml->asXML());
                }
            } catch (\Exception $e) {
                $this->addLog("Failed to fix SVG Image: $url");
                report($e);
                continue;
            }
        }
    }

    public function getImageRect($object): array
    {
        $width = $object['width'] * $object['scaleX'];
        $height = $object['height'] * $object['scaleY'];

        if ($object['type'] === 'text') {
            $width = $object['width'];
            $height = $object['height'];
        }

        $rect = [
            'centerX' => $object['left'],
            'centerY' => $object['top'],
            'width' => $width,
            'height' => $height
        ];

        $rect['left'] = $rect['centerX'] - $rect['width'] / 2;
        $rect['top'] = $rect['centerY'] - $rect['height'] / 2;
        $rect['right'] = $rect['left'] + $rect['width'];
        $rect['bottom'] = $rect['top'] + $rect['height'];
        $rect['angle'] = $object['angle'] ?? 0;

        return $rect;
    }

    function getJsonPath(): string
    {
        return self::DIRECTORY . "$this->user_id/$this->id/json.json";
    }

    public function getDesignJson($refresh = true): array
    {
        if (!$refresh) {
            $jsonData = spaces()->get($this->getJsonPath());
            if ($jsonData) {
                return json_decode($jsonData, true);
            }
        }

        $this->addLog("Generating JSON");

        $jsonData = $this->data;

        $viewport = $jsonData['meta']['viewport'];

        $json = [
            'id' => $this->id,
            'width' => $viewport['width'],
            'height' => $viewport['height'],
        ];

        $objects = [];

        $hasSvg = false;
        $hasGalleryImages = false;
        $hasText = false;
        $hasOverlayColor = false;
        $hasLargeImage = false;
        $hasMultiLineText = false;
        $hasFlippedImage = false;

        foreach ($jsonData['objects'] as $object) {
            $objectRect = $this->getImageRect($object);

            $width = $objectRect['width'];
            $height = $objectRect['height'];

            if ($width > 0 and $height > 0) {
                if (!empty($object['src'])) {
                    $type = 'image';

                    if (str_ends_with($object['src'], '.svg')) {
                        $hasSvg = true;
                    }

                    if (str_contains($object['src'], 'gallery')) {
                        $hasGalleryImages = true;
                    }

                    $src = $this->getImageSrcFromImageObject($object);

                    if ($object['overlayColor'] ?? null) {
                        $hasOverlayColor = true;
                    }

                    if (($object['flipX'] ?? false) || ($object['flipY'] ?? false)) {
                        $hasFlippedImage = true;
                    }

                    if (!str_ends_with($object['src'], '.svg') && $width * $height > 180000000) {
                        $hasLargeImage = true;
                    }
                } elseif (str_contains($object['type'], 'text') && !empty($object['text'])) {
                    $type = 'text';

                    $hasText = true;

                    $textLines = preg_split('/\r?\n/', $object['text']);
                    if (count($textLines) > 1) {
                        $hasMultiLineText = true;
                    }
                } else {
                    $type = 'outline';
                }

                $objects[] = [
                    // common attributes
                    'id' => $object['id'] ?? null,
                    'type' => $type,
                    'x' => (int)($objectRect['centerX'] - $viewport['centerX']),
                    'y' => (int)($objectRect['centerY'] - $viewport['centerY']),
                    'width' => $width,
                    'height' => $height,
                    'scaleX' => $object['scaleX'],
                    'scaleY' => $object['scaleY'],
                    'angle' => $object['angle'],
                    'rect' => $objectRect,
                    'isPattern' => $object['isPattern'] ?? null,

                    // image attributes
                    'image_type' => $object['type'] ?? null,
                    'mimeType' => $object['mimeType'] ?? null,
                    'src' => $src ?? null,
                    'flipX' => $object['flipX'] ?? false,
                    'flipY' => $object['flipY'] ?? false,
                    'fill' => $object['fill'] ?? 'none',
                    'overlayColor' => $object['overlayColor'] ?? null,
                    'overlayFilter' => $object['overlayFilter'] ?? null,
                    'filters' => $object['filters'] ?? null,
                    'opacity' => $object['opacity'] ?? null,

                    // text attributes
                    'baseLines' => $object['baseLines'] ?? [],
                    'leftLines' => $object['leftLines'] ?? [],
                    'text' => $object['text'] ?? null,
                    'fontSize' => (int)($object['fontSize'] ?? 40),
                    'fillColor' => $object['fill'] ?? null,
                    'backgroundColor' => $object['backgroundColor'] ?? null,
                    'fontWeight' => $object['fontWeight'] ?? 'normal',
                    'fontFamily' => $object['fontFamily'] ?? null,
                    'fontStyle' => $object['fontStyle'] ?? 'normal',
                    'underline' => $object['underline'] ?? false,
                    'textAlign' => $object['textAlign'] ?? 'left',

                    // outline attributes
                    'shape_type' => $object['type'] ?? null,
                    'left' => $object['left'] ?? null,
                    'top' => $object['top'] ?? null,
                    'strokeWidth' => $object['strokeWidth'] ?? 0,
                    'strokeColor' => $object['stroke'] ?? null,
                    'path' => $object['path'] ?? [],
                    'rx' => $object['rx'] ?? 0,
                    'ry' => $object['ry'] ?? 0,
                ];
            }
        }

        if ($hasSvg) {
            $this->setMetaData('has_svg', true);

            if (count($objects) === 1) {
                $this->setMetaData('has_single_svg', true);
            }
        }

        if ($hasGalleryImages) {
            $this->setMetaData('has_gallery_images', true);
        }

        if ($hasText) {
            $this->setMetaData('has_text', true);
        }

        if ($hasOverlayColor) {
            $this->setMetaData('has_overlay_color', true);
        }

        if ($hasLargeImage) {
            $this->setMetaData('has_large_image', true);
        }

        if ($hasMultiLineText) {
            $this->setMetaData('has_multi_line_text', true);
        }

        if ($hasFlippedImage) {
            $this->setMetaData('has_flipped_image', true);
        }

        $json['objects'] = $objects;

        spaces()->put($this->getJsonPath(), json_encode($json));

        return $json;
    }

    public function isPDF(): bool
    {
        return $this->user->getSetting('gangSheetFileExtension', '.png') === '.pdf';
    }

    public function isPNG(): bool
    {
        return $this->user->getSetting('gangSheetFileExtension', '.png') === '.png';
    }

    public function isTIFF(): bool
    {
        return $this->user->getSetting('gangSheetFileExtension', '.png') === '.tiff';
    }

    public function hasText(): bool
    {
        return $this->getMetaData('has_text', false);
    }

    public function hasSvg(): bool
    {
        return $this->getMetaData('has_svg', false);
    }

    public function hasGalleryImages(): bool
    {
        return $this->getMetaData('has_gallery_images', false);
    }

    public function hasOverlayColor(): bool
    {
        return $this->getMetaData('has_overlay_color', false);
    }

    public function hasLargeImage(): bool
    {
        return $this->getMetaData('has_large_image', false);
    }

    public function disableInkscape(): bool
    {
        return $this->getMetaData('disable_inkscape', false);
    }

    public function hasSingleSvgFile(): bool
    {
        return $this->getMetaData('has_single_svg', false);
    }

    public function hasMultiLineText(): bool
    {
        return $this->getMetaData('has_multi_line_text', false);
    }

    public function hasFlippedImage(): bool
    {
        return $this->getMetaData('has_flipped_image', false);
    }

    public function appendMemo($message): void
    {
        $memo = $this->getMetaData('memo', '');
        $memo .= "$message\n";
        $this->setMetaData('memo', $memo);
    }
}
