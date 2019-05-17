<?php

namespace App\Http\Controllers\Api\Woo;

use App\Http\Controllers\Controller;
use App\Jobs\OutputGangSheet;
use App\Models\Design;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DesignController extends Controller
{
    public function getDesigns(Request $request)
    {
        try {
            $data = $request->validate([
                'design_id' => 'nullable|string',
                'order_id' => 'nullable|numeric'
            ], [
                'design_id.string' => 'Design ID must be a string.',
                'order_id.numeric' => 'Order ID must be a number.'
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }

        $designId = $data['design_id'] ?? null;
        $orderId = $data['order_id'] ?? null;

        try {
            $shop = $request->user();

            if (!empty($designId)) {
                $designId = trim($designId);

                $designs = Design::where('user_id', $shop->id)
                    ->where('id', $designId)
                    ->get();

                return response()->json([
                    'success' => true,
                    'designs' => $designs
                ]);
            } else if (!empty($orderId)) {
                $orderId = trim($orderId);

                $order = Order::where('user_id', $shop->id)
                    ->where('wc_order_id', $orderId)
                    ->first();

                if (!$order) {
                    $order = $shop->pullOrder($orderId);
                }

                if ($order) {
                    $designs = $order->designs;

                    return response()->json([
                        'success' => true,
                        'designs' => $designs
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'designs' => []
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function getDesign($design_id, Request $request)
    {
        $shop = $request->user();

        $design = Design::where('user_id', $shop->id)->find($design_id);

        if (!$design) {
            return response()->json([
                'success' => false,
                'message' => 'Design not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'design' => $design
        ]);
    }

    public function getStatus($design_id)
    {
        $design = Design::find($design_id);

        return response()->json([
            'success' => true,
            'status' => $design->status
        ]);
    }

    function download(Request $request, $design_id)
    {
        try {
            $design = Design::withTrashed()->with(['order'])->findOrFail($design_id);
            $shop = $request->user();

            if ($design->isProcessing()) {
                return response([
                    'success' => false,
                    'error' => 'Design is being generated. Please try again later.'
                ]);
            }

            if (!$design->paid) {
                $order = $design->order;

                if (empty($order)) {
                    $order_id = $request->get('order_id');
                    if ($order_id) {
                        $order = $shop->pullOrder($order_id);
                    }
                }

                if (empty($order)) {
                    return response([
                        'success' => false,
                        'error' => "Can't pull the order. Please contact support."
                    ]);
                }

                $balance = $order->commission - $order->paid_amount;
                if ($balance > 0) {
                    $commission = min($balance, $design->commission);
                    if ($shop->credits < $commission) {
                        return response([
                            'success' => false,
                            'error' => 'Your credit is not enough.'
                        ]);
                    }
                    $shop->decrement('credits', $commission);
                }

                $design->update([
                    'paid' => true,
                    'paid_at' => now()
                ]);
            }

            $downloadUrl = URL::temporarySignedRoute(
                'woo.gang-sheet-image',
                now()->addMinutes(3),
                [
                    'design_id' => $design_id,
                    'file_name' => $design->getGangSheetFileName()
                ]
            );

            return response()->json([
                'success' => true,
                'url' => $downloadUrl
            ]);
        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function generate($design_id)
    {
        $design = Design::find($design_id);

        if (!$design) {
            return response()->json([
                'success' => false,
                'error' => 'Design not found.'
            ]);
        }

        OutputGangSheet::dispatch($design_id);

        return response()->json([
            'success' => true,
            'message' => 'Generating gang sheet...'
        ]);
    }
}
