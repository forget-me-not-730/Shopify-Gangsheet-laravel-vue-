<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Repositories\ShopRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    function index(Request $request)
    {
        $user = $request->user();
        $products = ShopRepository::getProducts($user);

        return inertia('Merchant/Products', [
            'products' => $products,
        ]);
    }

    function detail(Request $request)
    {
        $query = Product::withTrashed();

        if ($request->has('product_id')) {
            $query->where('id', $request->product_id);
            $product = $query->with(['sizes'])->first();
        } elseif ($request->has('woo_product_id')) {
            $query->where('woo_product_id', $request->woo_product_id)
                ->where('user_id', $request->user()->id);
            $product = $query->with(['sizes'])->first();
        }

        return inertia('Merchant/ProductDetail', [
            'product' => $product ?? null,
        ]);
    }

    function save(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'nullable|integer',
            'woo_product_id' => 'nullable|integer',
            'title' => 'required|string|max:100',
            'slug' => 'required|string',
            'description' => 'nullable|string',
            'redirect_url' => 'required|string',
            'sizes' => 'required|array',
            'button_text' => 'required|string',
            'button_background_color' => 'required|string',
            'button_text_color' => 'required|string',
            'status' => 'required|string',
            'disableUploadingImage' => 'required|boolean',
            'printFileName' => 'required|boolean',
            'printFileNamePosition' => 'nullable|string',
            'printFileNameHeight' => 'nullable|numeric',
            'nameAndNumber' => 'required|array',
            'nameAndNumber.size.name' => 'required|array',
            'nameAndNumber.size.name.*' => 'required|numeric',
            'nameAndNumber.size.number' => 'required|array',
            'nameAndNumber.size.number.*' => 'required|numeric',
            'nameAndNumber.enabled' => 'required|boolean',
            'nameAndNumber.unit' => 'required|string',
            'productPattern' => 'required|array',
            'art_board_type' => 'required|numeric',
            'printerWidth' => 'required|numeric',
            'startHeight' => 'required|numeric',
            'minHeight' => 'required|numeric',
            'maxHeight' => 'required|numeric',
            'pricing' => 'required|array',
        ]);

        if (isset($data['id'])) {
            $product = Product::withTrashed()->findOrFail($data['id']);
        } else {
            $product = new Product();
        }
        $user = $request->user();

        $slug_check = Product::where('user_id', $user->id)
            ->where(function (Builder $query) use ($data) {
                if (isset($data['id'])) {
                    $query->whereNot('id', $data['id']);
                }
            })
            ->where('slug', $data['slug'])
            ->exists();

        if ($slug_check) {
            return redirect()->back()->withErrors(['slug' => 'Slug already Exists']);
        }

        $product->fill([
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'type' => $data['art_board_type'],
            'description' => $data['description'] ?? '',
            'redirect_url' => $data['redirect_url'] ?? '',
        ]);

        $product->button = [
            'text' => $data['button_text'],
            'background_color' => $data['button_background_color'],
            'text_color' => $data['button_text_color'],
        ];

        if (!empty($data['woo_product_id'])) {
            $deleted_varaints = [];
            $variants = [];
            foreach ($data['sizes'] as $size_data) {

                if (isset($size_data['id'])) {
                    if (isset($size_data['delete'])) {
                        $deleted_varaints[] = $size_data['woo_variant_id'];
                    } else {
                        $variants[] = $size_data;
                    }
                } else {
                    $variants[] = $size_data;
                }
            }

            $wooProductData = [
                'product_id' => $data['woo_product_id'],
                'product_title' => $data['title'],
                'btnLabel' => $data['button_text'],
                'artBoardType' => $data['art_board_type'],
                'variants' => $variants,
                'deleted_variants' => $deleted_varaints
            ];

            $variants = $user->updateWooProduct($wooProductData);

            if (empty($variants)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update product'
                ], 500);
            }
        }

        $product->save();

        $product->setSetting([
            'disableUploadingImage' => $data['disableUploadingImage'] ?? null,
            'printFileName' => $data['printFileName'] ?? null,
            'printFileNamePosition' => $data['printFileNamePosition'] ?? null,
            'printFileNameHeight' => $data['printFileNameHeight'] ?? null,
            'nameAndNumber' => $data['nameAndNumber'] ?? null,
            'productPattern' => $data['productPattern'] ?? null,
            'pricing' => $data['pricing'] ?? null,
            'printerWidth' => $data['printerWidth'] ?? null,
            'startHeight' => $data['startHeight'] ?? null,
            'minHeight' => $data['minHeight'] ?? null,
            'maxHeight' => $data['maxHeight'] ?? null,
        ]);

        if ($data['status'] == 'active') {
            $product->restore();
        } else {
            $product->delete();
        }

        if (empty($data['woo_product_id'])) {
            foreach ($data['sizes'] as $size_data) {

                if (isset($size_data['id'])) {
                    if (isset($size_data['delete'])) {
                        ProductSize::destroy($size_data['id']);
                    } else {
                        $variant = ProductSize::findOrFail($size_data['id']);
                        $variant->update($size_data);
                    }
                } else {
                    $product->sizes()->create($size_data);
                }
            }
        }

        return redirect()->route('merchant.product.detail', ['product_id' => $product->id]);
    }

    public function pattern($product_id)
    {
        $product = Product::with('sizes')
            ->findOrFail($product_id);
        $merchant = app('shop');

        return inertia('Merchant/PatternBuilder', [
            'product' => $product,
            'merchant' => $merchant,
        ]);
    }

    public function savePattern($product_id, Request $request)
    {
        $data = $this->validate($request, [
            'patterns' => 'required|array',
        ]);

        $patterns = $data['patterns'];

        $product = Product::findOrFail($product_id);
        $sizes = $product->sizes;

        foreach ($patterns as $pattern) {
            $size = $sizes->where('id', $pattern['variant_id'])->first();
            if ($size) {
                $size->update([
                    'pattern' => $pattern['pattern'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
