<?php

namespace App\Repositories;

use App\Exceptions\DesignNotEditableException;
use App\Jobs\OutputGangSheet;
use App\Models\Design;
use Illuminate\Support\Str;

class DesignRepository extends BaseRepository
{
    /**
     * @throws DesignNotEditableException
     */
    public function createOrUpdate(array $data): Design
    {
        $jsonData = $data['json'];

        $newDesign = [
            'product_id' => $data['product_id'] ?? null,
            'user_id' => $data['shop_id'],
            'size_id' => $data['variant_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'quantity' => $data['quantity'],
            'session_id' => $data['session_id'] ?? null,
            'data' => $jsonData,
            'name' => $jsonData['name'] ?? 'New Gang Sheet',
            'type' => $data['type'] ?? Design::TYPE_GANG_SHEET,
        ];

        $design_id = $data['design_id'] ?? null;

        $design = Design::withTrashed()->find($design_id);

        if ($design?->order && !$design?->allowedEdit($data['token'] ?? null)) {
            throw new DesignNotEditableException();
        }

        if (empty($design)) {
            $design_id = Str::uuid()->toString();
            $newDesign['id'] = $design_id;
            $newDesign['data']['designId'] = $design_id;
            $newDesign['status'] = Design::STATUS_CREATED;
            $design = Design::create($newDesign);
        } else {
            $design->update($newDesign);
        }

        if ($design->order) {
            OutputGangSheet::dispatch($design_id);

            if ($design->edit_request === Design::EDIT_REQUEST_APPROVED) {
                $design->processedEditRequest();
            }
        }

        $updatedAt = now()->format('Y-m-d H-i-s');
        $newDesign['userAgent'] = request()->userAgent();
        storage()->put("designs/{$design->id}/$updatedAt.json", json_encode($newDesign));

        if ($jsonData['submitActualHeight'] ?? false) {
            $design->setMetaData('actual_height', $jsonData['actualHeight']);
        } else {
            $design->setMetaData('actual_height', null);
        }

        if (!empty($jsonData['meta']['build_type'])) {
            $design->setMetaData('build_type', $jsonData['meta']['build_type']);
        }

        $thumbnail = explode(',', $data['thumbnail'])[1];
        $thumbnail = base64_decode($thumbnail);
        spaces()->put($design->thumbnail_path, $thumbnail);

        if (empty($design->access_token)) {
            $design->update(['access_token' => Str::random(20)]);
        }

        return $design;
    }

    static public function getImages(Design $design): array
    {
        $images = [];

        $json = $design->getDesignJson(false);

        foreach ($json['objects'] as $object) {
            $width = round($object['width'] / 300, 2);
            $height = round($object['height'] / 300, 2);

            if ($object['type'] === 'image') {

                // Find index from the images by the url
                $image = collect($images)->where('url', $object['src'])
                    ->where('width', $width)
                    ->where('height', $height)
                    ->first();

                if ($image) {
                    $image['count']++;
                    continue;
                }

                $images[] = [
                    'type' => 'image',
                    'url' => $object['src'],
                    'count' => 1,
                    'width' => $width,
                    'height' => $height,
                    'unit' => 'inch',
                ];
            } else if ($object['type'] === 'text') {
                // Find index from the images by the text
                $text = collect($images)->where('text', $object['text'])
                    ->where('width', $width)
                    ->where('height', $height)
                    ->first();

                if ($text) {
                    $text['count']++;
                    continue;
                }

                $images[] = [
                    'type' => 'text',
                    'text' => $object['text'],
                    'count' => 1,
                    'width' => $width,
                    'height' => $height,
                    'unit' => 'inch',
                ];
            }
        }

        return $images;
    }

    static public function toJson(Design $design): array
    {
        if ($size = $design->data['meta']['variant'] ?? null) {
            unset($size['useHiddenVariants']);
            unset($size['visible']);
        }

        return [
            'id' => $design->id,
            'status' => $design->status,
            'name' => $design->name,
            'quantity' => $design->quantity,
            'size' => $size,
            'order_type' => $design->getMetaData('build_type', 'unknown'),
            'edit_url' => $design->customer_edit_url,
            'thumbnail_url' => $design->thumbnail_url,
            'download_url' => $design->download_url,
            'images' => self::getImages($design),
        ];
    }
}
