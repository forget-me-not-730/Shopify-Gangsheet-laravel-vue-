<?php

namespace App\Http\Controllers\Woo;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Design;
use App\Repositories\DesignRepository;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WooBuilderController extends Controller
{
    public function __construct(private readonly DesignRepository $designRepository)
    {
    }

    public function index(Request $request)
    {
        $data = $this->validate($request, [
            'product' => 'required|integer',
            'variant' => 'required|integer',
            'quantity' => 'required|integer',
            'customer_id' => 'nullable|integer'
        ]);

        $shop = $request->shop;

        $product = ShopRepository::getWooProduct($shop, $data['product']);

        if (!empty($product) && !empty($product['sizes'])) {

            $variantFound = false;
            foreach ($product['sizes'] as $variant) {
                if ($variant['woo_variant_id'] == $data['variant']) {
                    $variantFound = true;
                    break;
                }
            }

            if ($variantFound) {
                $customerId = $data['customer_id'] ?? null;

                if ($customerId) {
                    $customer = $shop->pullCustomer($customerId);
                    $customer?->loadDesignsCount();
                }

                $props = [
                    'shop' => $shop,
                    'product' => $product,
                    'variant' => $data['variant'],
                    'quantity' => $data['quantity'],
                    'customer' => $customer ?? null
                ];

                if ($product->isRollingGangSheet()) {
                    return inertia('Woo/RollingGangSheetPage', $props);
                }

                return inertia('Woo/BuilderPage', $props);
            }
        }

        return inertia('Customer/InvalidProduct', [
            'merchant' => $shop
        ]);
    }

    public function editDesign(Request $request)
    {
        $data = $this->validate($request, [
            'design_id' => 'required|string',
            'token' => 'nullable|string|min:20|max:255'
        ]);

        $shop = $request->shop;

        $design = Design::withTrashed()->where([
            'user_id' => $shop->id,
            'id' => $data['design_id']
        ])->firstOrFail();

        if (!$design->allowedEdit($data['token'] ?? null)) {
            return inertia('Customer/SendEditRequest', [
                'design' => $design
            ]);
        }

        $product = ShopRepository::getWooProduct($shop, $design->product_id);

        if ($design->customer_id) {
            $customer = Customer::find($design->customer_id);

            if (!$design->isCompleted()) {
                if ($customer->user_id !== $shop->id) {
                    $customer = $shop->pullCustomer($design->customer_id);
                }
            }

            $customer?->loadDesignsCount();
        }

        $props = [
            'shop' => $shop,
            'designStatus' => $design->status,
            'variant' => $data['variant'] ?? null,
            'product' => $product ?? null,
            'designJson' => $design->data['raw'] ?? $design->data,
            'order' => $design->order ?? null,
            'customer' => $customer ?? null,
            'token' => $data['token'] ?? null,
            'editRequest' => $design->edit_request ?? '',
            'edit' => true,
        ];

        if ($product->isRollingGangSheet()) {
            return inertia('Woo/RollingGangSheetPage', $props);
        }

        return inertia('Woo/BuilderPage', $props);
    }

    public function saveDesign(Request $request)
    {
        $data = $this->validate($request, [
            'design_id' => 'nullable|string',
            'product_id' => 'nullable|numeric',
            'variant_id' => 'required|integer',
            'quantity' => 'nullable|integer',
            'session_id' => 'nullable|string',
            'customer_id' => 'nullable|numeric',
            'json' => 'required|array',
            'shop_id' => 'required|integer',
            'thumbnail' => 'required|string',
            'token' => 'nullable|string|min:20|max:255',
            'type' => 'nullable|integer'
        ]);

        $design = $this->designRepository->createOrUpdate($data);

        return response()->json([
            'success' => true,
            'preview_url' => route('woo.get-preview-image', $design->id . '.png'),
            'download_url' => route('woo.get-design-image', $design->id . '.png'),
            'design_id' => $design->id
        ]);
    }

    public function getGangSheetImage(Request $request, $design_id)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Unauthorized access.');
        }

        $design = Design::with(['merchant', 'order'])->findOrFail($design_id);

        if (empty($design) || empty($design->merchant)) {
            abort(404, 'file does not exist.');
        }

        $expires = now()->addMinutes(10);
        if (spaces()->exists($design->image_path)) {
            $gangSheetPath = $design->image_path;
        } else if (spaces()->exists(Design::DIRECTORY . $design->id . '.png')) {
            $gangSheetPath = Design::DIRECTORY . $design->id . '.png';
        }

        if (!empty($gangSheetPath)) {

            $fileName = $design->getGangSheetFileName();

            $fileUrl = spaces()->temporaryUrl($gangSheetPath, $expires, [
                'ResponseContentDisposition' => 'attachment; filename=' . $fileName,
                'url' => config('filesystems.disks.spaces.url')
            ]);

            if (!auth('web')->user()?->isAdmin() && empty(session()->get('admin_id'))) {
                $design->update(['downloaded_at' => now()]);
            }

            return redirect()->to($fileUrl);
        }

        return 'Image is still being generated.';
    }

    public function getDesignImage($fileName)
    {

        $design_id = explode('.', basename($fileName))[0];
        $design = Design::where(['id' => $design_id])->first();

        if ($design->order) {
            return redirect()->route('gang-sheet-image', ['design_id' => $design_id]);
        }

        $contentType = 'image/png';

        $filePath = $design->watermark_path;
        if (Storage::disk('spaces')->exists($filePath)) {
            $file = Storage::disk('spaces')->get($filePath);

            $headers = [
                'Content-Type' => $contentType
            ];

            return response($file, 200, $headers);
        }

        return null;
    }

    public function getPreviewImage($file_name)
    {
        [$design_id] = explode('.', $file_name);
        $design = Design::findOrFail($design_id);

        return view('preview', [
            'design' => $design,
            'image_url' => $design->thumbnail_url,
        ]);
    }

    public function getThumbnailImage($file_name)
    {
        $design_id = explode('.', $file_name)[0];
        $design = Design::findOrFail($design_id);

        return redirect()->to($design->thumbnail_url);
    }
}
