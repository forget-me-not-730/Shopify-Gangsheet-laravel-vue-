<?php

namespace App\GangSheet\Traits;

use App\GangSheet\Facades\Canva;
use App\Models\CanvaImage;
use Illuminate\Http\Request;

trait CanvaDesignController
{
    public function getExportedCanvaImage(Request $request)
    {
        $data = $this->validate($request, [
            'canva_id' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        $canvaImages = CanvaImage::where('canva_id', $data['canva_id'])
            ->where('user_id', $data['user_id'])
            ->get();

        if ($canvaImages->count() > 0) {
            return response()->json([
                'success' => true,
                'images' => $canvaImages
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'No exported designs available.'
            ]);
        }
    }

    public function uploadCanvaImage(Request $request)
    {
        $data = $this->validate($request, [
            'thumb_url' => 'required|string',
            'canva_id' => 'required|string',
            'user_id' => 'required|integer',
            'customer_id' => 'nullable|numeric',
            'session_id' => 'required|string',
            'title' => 'nullable|string',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
            'page_count' => 'nullable|integer'
        ]);

        $canvaImage = CanvaImage::where('canva_id', $data['canva_id'])
            ->where('user_id', $data['user_id'])
            ->whereBetween('width', [$data['width'] - 3, $data['width'] + 3])
            ->whereBetween('height', [($data['height'] - 3) * $data['page_count'], ($data['height'] + 3) * $data['page_count']])
            ->first();

        if (empty($canvaImage)) {
            $canvaImage = CanvaImage::create([
                'user_id' => $data['user_id'],
                'canva_id' => $data['canva_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'session_id' => $data['session_id'],
                'title' => $data['title'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
                'extension' => 'png',
                'status' => CanvaImage::STATUS_PENDING
            ]);
        } else {
            $canvaImage->update([
                'customer_id' => $data['customer_id'] ?? null,
                'session_id' => $data['session_id'],
            ]);
        }

        $res = $canvaImage->upload($data['width'], $data['height']);

        return response()->json($res);
    }

    public function deleteCanvaImage(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'required|string',
            'user_id' => 'required|integer',
            'canva_id' => 'required|string',
        ]);

        $canvaImage = CanvaImage::find($data['id']);
        $canvaImage->delete();

        return response()->json([
            'success' => true,
            'deletedId' => $canvaImage->id
        ]);
    }

    public function getCustomerCanvaDesigns(Request $request)
    {
        $data = $this->validate($request, [
            'access_token' => 'required|string',
            'query' => 'nullable|string',
        ]);

        $designs = Canva::getDesignsByAccessToken($data['access_token'], $data['query'] ?? null);

        if ($designs) {
            return response()->json([
                'success' => true,
                'designs' => $designs
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Unable to fetch designs.'
            ]);
        }
    }
}
