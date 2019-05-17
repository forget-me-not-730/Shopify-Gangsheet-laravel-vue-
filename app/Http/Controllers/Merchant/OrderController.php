<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Order;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends Controller
{
    function index(Request $request)
    {
        $shop = $request->user();

        $perPage = $request->input('perPage', 10);
        $search = $request->input('search', '');
        $status = $request->input('status', 'orders');
        $searchBy = $request->input('searchBy', 'product');

        $orders = QueryBuilder::for(Order::class)
            ->allowedFields(['id', 'name', 'email', 'phone', 'price', 'status', 'created_at',])
            ->allowedSorts(['id', 'name', 'email', 'phone', 'price', 'status', 'product_title', 'designs_count', 'created_at',])
            ->withAggregate('product', 'title')
            ->where('user_id', $shop->id)
            ->when($status !== 'all', function ($query) use ($status) {
                if ($status === 'archived') {
                    return $query->where('status', '=', 'archived');
                }
                return $query->where('status', '!=', 'archived');
            })
            ->withCount('designs')
            ->with([
                'designs' => function ($q) {
                    $q->withTrashed()
                        ->with('product:id,title', 'size:id,label,price', 'merchant:id,slug')
                        ->select('id', 'order_id', 'user_id', 'product_id', 'size_id', 'status', 'data', 'quantity', 'downloaded_at');
                }
            ])
            ->when($search ?? false, function ($query) use ($search, $searchBy) {
                $searchLike = '%' . $search . '%';

                switch ($searchBy) {
                    case 'product':
                        $query->whereHas('designs.product', function ($q) use ($searchLike) {
                            $q->where('title', 'like', $searchLike);
                        });
                        break;
                    case 'name':
                        $query->where('name', 'like', $searchLike);
                        break;
                    case 'email':
                        $query->where('email', 'like', $searchLike);
                        break;
                    default:
                        $query->whereHas('designs.product', function ($q) use ($searchLike) {
                            $q->where('title', 'like', $searchLike);
                        });
                        break;
                }

                return $query;
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $orders->getCollection()->transform(function ($order) {
            $order->designs->withoutAppends(['thumbnail_url', 'watermark_url']);
            return $order;
        });

        return inertia('Merchant/Orders', [
            'orders' => $orders,
            'filters' => [
                'perPage' => $perPage,
                'search' => $search,
                'status' => $status,
                'searchBy' => $searchBy,
            ]
        ]);
    }

    function update(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'required|integer',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'status' => 'required|string',
        ]);
        $order = Order::findOrFail($data['id']);
        $order->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => $data['status'],
        ]);

        return redirect()->back();
    }

    function archive(Request $request)
    {
        $data = $this->validate($request, [
            'orders' => 'required|array',
            'orders.*' => 'exists:orders,id'
        ]);
        Order::whereIn('id', $data['orders'])->update(['status' => 'archived']);

        return response()->json([
            'success' => true,
            'message' => 'Orders have been successfully archived.',
        ]);
    }

    function download($id, Request $request)
    {
        $design = Design::withTrashed()->with(['order', 'size'])->findOrFail($id);

        if (!$design->confirmPaid()) {
            return redirect()->back()->withErrors(['message' => 'Your credit is not enough.']);
        }

        $expires = now()->addMinutes(10);
        if (Storage::disk('spaces')->exists($design->image_path)) {
            $gangSheetPath = $design->image_path;
        } else if (Storage::disk('spaces')->exists(Design::DIRECTORY . $design->id . '.png')) {
            $gangSheetPath = Design::DIRECTORY . $design->id . '.png';
        }

        if (!empty($gangSheetPath)) {

            $fileName = $design->getGangSheetFileName();

            $fileUrl = spaces()->temporaryUrl($gangSheetPath, $expires, [
                'ResponseContentDisposition' => 'attachment; filename=' . $fileName,
                'url' => config('filesystems.disks.spaces.url')
            ]);

            if (!auth('web')->user()->isAdmin() && empty(session()->get('admin_id'))) {
                $design->update(['downloaded_at' => now()]);
            }

            return redirect()->to($fileUrl);
        }

        return 'The sheet is still being generated. Try again later.';
    }

    public function editDesign(Design $design)
    {
        $design->load([
            'size',
            'merchant',
            'customer',
            'order'
        ]);

        $product = ShopRepository::getWooProduct($design->merchant, $design->product_id);

        $props = [
            'id' => $design->id,
            'product' => $product,
            'size' => $design->data['meta']['variant'] ?? $design->size,
            'merchant' => $design->merchant,
            'order' => $design->order,
            'customer' => $design->customer,
            'quantity' => $design->quantity,
            'data' => $design->data,
            'newDesign' => false,
            'admin' => true,
            'editMode' => true,
            'token' => $design->access_token
        ];

        if ($product->isRollingGangSheet()) {
            return inertia('Customer/RollingGangSheetPage', $props);
        }

        return inertia('Customer/BuilderPage', $props);
    }

    public function getDesignStatus(Design $design)
    {
        return response()->json([
            'success' => true,
            'status' => $design->status
        ]);
    }
}
