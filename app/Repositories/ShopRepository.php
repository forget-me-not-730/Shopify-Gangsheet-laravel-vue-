<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ShopRepository
{
    static public function getWooProduct($shop, $productId): ?Product
    {
        if ($productId) {
            $product = Product::where('user_id', $shop->id)
                ->where('woo_product_id', $productId)
                ->with('sizes')
                ->first();

            if (empty($product)) {
                $product = $shop->getWooProduct($productId);
            }

            if (empty($product)) {
                $product = Product::with('sizes')->find($productId);
            }

            if ($product) {
                $product['variants'] = $product['sizes']->map(function ($size) {
                    return [
                        'id' => $size['woo_variant_id'],
                        'width' => $size['width'],
                        'height' => $size['height'],
                        'label' => $size['label'],
                        'unit' => $size['unit'],
                        'price' => $size['price'],
                        'maxAllowedFileCount' => $size['maxAllowedFileCount'] ?? -1
                    ];
                });
            }
        }

        return $product ?? null;
    }

    static public function getProducts($shop)
    {
        if ($shop->isWooStore()) {
            $products = $shop->cacheProducts();

            foreach ($products as $product) {
                $product['variants'] = $product['sizes']->map(function ($size) {
                    return [
                        'id' => $size['woo_variant_id'],
                        'width' => $size['width'],
                        'height' => $size['height'],
                        'label' => $size['label'],
                        'unit' => $size['unit'],
                        'price' => $size['price'],
                        'maxAllowedFileCount' => $size['maxAllowedFileCount'] ?? -1
                    ];
                });
            }
        } else {
            $products = Product::withTrashed()
                ->where('user_id', $shop->id)
                ->withCount(['sizes', 'orders'])
                ->with(['sizes'])
                ->latest()
                ->get();
        }

        return $products;
    }
}
