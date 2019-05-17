<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\Queue;
use App\Jobs\OutputGangSheet;

class DesignController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = $request->input('perPage') ?? 10;
        $orderStatus = $request->input('order_status', 'all');
        $designStatus = $request->input('design_status', 'all');
        $search = $request->input('search', '');
        $searchBy = $request->input('searchBy', 'design_id');

        $query = DB::table('designs')
            ->select([
                'designs.id',
                'designs.user_id',
                'designs.customer_id',
                'designs.order_id',
                'designs.status',
                'designs.file_name',
                'designs.type',
                'designs.data',
                'designs.paid',
                'designs.paid_at',
                'designs.created_at'
            ])
            ->where('designs.user_id', $user->id);

        if (!$user->isCustomStore()) {
            $query->leftJoin('orders', 'designs.order_id', '=', 'orders.id')
                ->leftJoin('customers', 'designs.customer_id', '=', 'customers.id')
                ->addSelect([
                    'orders.status as order_status',
                    DB::raw('COALESCE(orders.name, customers.name) as customer_name'),
                    DB::raw('COALESCE(orders.email, customers.email) as customer_email'),
                ]);

            if ($orderStatus != 'all') {
                if ($orderStatus == 'in-cart') {
                    $query->whereNull('order_id');
                } else {
                    $query->whereNotNull('order_id');
                }
            }
        }

        if ($designStatus != 'all') {
            $query->where('designs.status', $designStatus);
        }

        $designs = $query->when($search ?? false, function ($query) use ($search, $searchBy) {
            $searchLike = '%' . $search . '%';

            switch ($searchBy) {
                case 'customer':
                    $query->whereRaw("COALESCE(orders.name, customers.name) LIKE ?", [$searchLike]);
                    break;
                default:
                    $query->where('designs.id', 'like', $searchLike);
                    break;
            }

            return $query;
        })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Merchant/Designs', [
            'designs' => $designs,
            'filters' => [
                'order_status' => $orderStatus,
                'design_status' => $designStatus,
                'search' => $search,
                'searchBy' => $searchBy,
            ]
        ]);
    }

    public function editDesign($design_id)
    {
        $user = \request()->user();

        if ($user->isCustomStore()) {
            $designEditUrl = route('gs.builder.edit', [
                'shop_id' => $user->shop_uuid,
                'design_id' => $design_id,
            ]);
        } else {
            $designEditUrl = "https://{$user->merchantDomain()}/builder/design/{$design_id}";
        }

        return redirect()->to($designEditUrl);
    }

    public function download($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        if (!$design->isCompleted()) {
            return 'Gang Sheet is not generated yet.';
        }

        if ($design->merchant->isGangSheetPublic() || $design->isPaid()) {
            spaces()->setVisibility($design->image_path, 'public');
            return redirect()->to($design->image_url);
        }

        return redirect()->to($design->watermark_url);
    }

    public function payAndDownload($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        if (!$design->isCompleted()) {
            return 'Gang Sheet is not generated yet.';
        }

        if (!($design->merchant->isGangSheetPublic() || $design->isPaid())) {
            if (!$design->confirmPaid()) {
                return redirect()->back()->withErrors(['message' => 'Your credit is not enough.']);
            }
        }

        return redirect()->to($design->image_url);
    }

    public function preview($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        if ($design->isCompleted()) {
            return redirect()->to($design->watermark_url);
        }

        return redirect()->to($design->thumbnail_url);
    }

    public function thumbnail($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        return redirect()->to($design->thumbnail_url);
    }

    public function status($design_id)
    {
        $design = Design::withTrashed()->findOrFail($design_id);

        if ($design->isCompleted()) {
            $metaData = $design->metaData;
        }

        return response()->json([
            'success' => true,
            'status' => $design->status,
            'meta_data' => $metaData ?? null
        ]);
    }

    public function generate($design_id)
    {
        $user = auth()->user();

        if (Design::where('id', $design_id)->doesntExist()) {
            return response()->json([
                'success' => false,
                'message' => 'Design not found'
            ], 404);
        }

        $design = Design::withTrashed()->find($design_id);

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

        OutputGangSheet::dispatch($design_id, [
            'queue' => Queue::GANG_SHEET_THREE->value
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gang sheet generation started'
        ], 200);
    }
}
