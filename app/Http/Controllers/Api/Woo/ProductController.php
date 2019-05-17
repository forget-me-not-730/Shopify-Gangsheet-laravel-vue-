<?php

namespace App\Http\Controllers\Api\Woo;

use App\Models\Product;
use App\Repositories\ShopRepository;
use Illuminate\Http\Request;

class ProductController
{
    public function getProducts(Request $request)
    {
        $shop = $request->user();

        return response()->json([
            'success' => true,
            'products' => ShopRepository::getProducts($shop)
        ]);
    }

    public function updateProduct(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer',
                'woo_product_id' => 'required|integer',
                'title' => 'required|string',
                'slug' => 'required|string',
                'description' => 'nullable|string',
                'unit' => 'nullable|string',
                'variants' => 'required|array',
                'btnLabel' => 'required|string',
                'art_board_type' => 'required|integer',
                'deleted_variants' => 'nullable|array',
                'disableUploadingImage' => 'required|boolean',
                'printFileName' => 'required|boolean',
                'printFileNamePosition' => 'required|string',
                'printFileNameHeight' => 'required|numeric',
                'printerWidth' => 'nullable|numeric',
                'startHeight' => 'nullable|numeric',
                'minHeight' => 'nullable|numeric',
                'maxHeight' => 'nullable|numeric',
                'pricing' => 'nullable|array',
                'nameAndNumber' => 'required|array',
                'nameAndNumber.size.name' => 'required|array',
                'nameAndNumber.size.name.*' => 'required|numeric',
                'nameAndNumber.size.number' => 'required|array',
                'nameAndNumber.size.number.*' => 'required|numeric',
                'nameAndNumber.enabled' => 'required|boolean',
                'nameAndNumber.unit' => 'required|string',
            ]);

            $product = Product::withTrashed()->findOrFail($data['id']);

            $shop = $request->user();

            $product->fill([
                'user_id' => $shop->id,
                'title' => $data['title'],
                'type' => $data['art_board_type'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
            ]);

            $product->button = [
                'text' => $data['btnLabel']
            ];

            $wooProductData = [
                'product_id' => $data['woo_product_id'],
                'product_title' => $data['title'],
                'btnLabel' => $data['btnLabel'],
                'artBoardType' => $data['art_board_type'],
                'variants' => $data['variants'],
                'deleted_variants' => $data['deleted_variants']
            ];

            $variants = $shop->updateWooProduct($wooProductData);

            if (empty($variants)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update product'
                ], 500);
            }

            $product->save();

            $product->setSetting([
                // rolling product settings
                'printerWidth' => $data['printerWidth'] ?? null,
                'startHeight' => $data['startHeight'] ?? null,
                'minHeight' => $data['minHeight'] ?? null,
                'maxHeight' => $data['maxHeight'] ?? null,
                'pricing' => $data['pricing'] ?? null,
                'unit' => $data['unit'] ?? null,

                // builder settings
                'nameAndNumber' => $data['nameAndNumber'] ?? null,
                'disableUploadingImage' => $data['disableUploadingImage'] ?? null,
                'printFileName' => $data['printFileName'] ?? null,
                'printFileNamePosition' => $data['printFileNamePosition'] ?? null,
                'printFileNameHeight' => $data['printFileNameHeight'] ?? null,
            ]);

            $wooProducts = $shop->cacheProducts($data['woo_product_id']);

            return response()->json([
                'success' => true,
                'product' => $wooProducts[0]
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Cache product failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
