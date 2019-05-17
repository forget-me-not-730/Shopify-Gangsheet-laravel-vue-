<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Queue;
use App\Jobs\OutputGangSheet;
use App\Models\Design;
use App\Repositories\DesignRepository;
use Illuminate\Http\Request;

class DesignController
{
    public function getDesign($design_id)
    {
        $user = auth()->user();

        $design = Design::find($design_id);

        if (empty($design)) {
            return response()->json([
                'success' => false,
                'message' => 'Design not found'
            ], 404);
        }

        if ($user->id !== $design->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        if ($design->isCompleted()) {
            if ($user->credits > -1 && !$design->paid) {
                if ($user->credits < $design->commission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your credit is not enough.'
                    ], 400);
                }

                $user->decrement('credits', $design->commission);

                $design->update([
                    'paid' => true,
                    'paid_at' => now()
                ]);
            }

            spaces()->setVisibility($design->image_path, 'public');
        }

        return response()->json([
            'success' => true,
            'design' => DesignRepository::toJson($design)
        ], 200);
    }

    public function generateDesign(Request $request)
    {
        $data = $request->validate([
            'design_id' => 'required|string',
            'file_name' => 'nullable|string',
        ]);

        $user = auth()->user();

        if (Design::where('id', $data['design_id'])->doesntExist()) {
            return response()->json([
                'success' => false,
                'message' => 'Design not found'
            ], 404);
        }

        $design = Design::withTrashed()->find($data['design_id']);

        if ($design->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        if ($design->isProcessing()) {
            return response()->json([
                'success' => false,
                'message' => 'Design is already processing'
            ], 400);
        }

        if ($data['file_name'] ?? null) {
            $design->file_name = $data['file_name'];
            $design->save();
        }

        $processingDesigns = Design::where('user_id', $user->id)
            ->whereIn('status', [Design::STATUS_PENDING, Design::STATUS_PROCESSING])
            ->count();

        $design->update([
            'status' => Design::STATUS_PENDING
        ]);

        OutputGangSheet::dispatch($data['design_id'])->delay(now()->addSeconds($processingDesigns * 5));

        return response()->json([
            'success' => true,
            'message' => 'Gang sheet generation started'
        ], 200);
    }
}
