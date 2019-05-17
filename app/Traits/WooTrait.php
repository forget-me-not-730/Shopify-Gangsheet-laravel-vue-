<?php

namespace App\Traits;

use App\Enums\Queue;
use App\Jobs\OutputGangSheet;
use App\Models\Customer;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

trait WooTrait
{
    private string $prefix = '/wp-json/gang_sheet/v1/';

    private function getWooUrl($path): string
    {
        $prefix = $this->getSetting('woo_api_prefix', $this->prefix);
        return $this->website . $prefix . $path;
    }

    public function wooApi(string $method, string $path, array $params = [], $withCredentials = true): array|null
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            if ($withCredentials) {
                $token = $this->getPlainTextToken();
                $headers['X-GS-Token'] = "Bearer {$token}";
            }

            $params['t'] = time();

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => str_starts_with('https', $this->website)])
                ->{$method}($this->getWooUrl($path), $params);

            // Check if the request was successful (status code 2xx)
            if ($response->successful()) {
                // Get the response body as an array or JSON object
                return $response->json();
            } else {
                // Handle the error, log, or take appropriate action
                $errorCode = $response->status();
                $errorMessage = $response->body();

                return [
                    'error' => true,
                    'code' => $errorCode,
                    'message' => $errorMessage
                ];
            }
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            return [
                'error' => true,
                'message' => $message
            ];
        }
    }

    public function getWooProduct($product_id): ?Product
    {
        $response = $this->wooApi("get", "product/" . $product_id);

        if ($response && empty($response['error']) && $response['success']) {

            $product = $response['product'];

            if (!empty($product['variants'])) {
                foreach ($product['variants'] as &$variant) {
                    $variant['width'] = floatval($variant['width']);
                    $variant['height'] = floatval($variant['height']);
                    $variant['unit'] = ($variant['unit'] ?? null) ? $variant['unit'] : 'in';
                }
            }

            return $this->saveProduct($product);
        }

        return null;
    }

    public function getWooProducts()
    {
        $response = $this->wooApi("get", "products");

        if (empty($response['error'])) {
            return $response['products'];
        }

        return [];
    }

    public function updateWooProduct($data)
    {
        if ($data['product_id']) {
            $response = $this->wooApi("post", "product/" . $data['product_id'], $data);

            if (empty($response['error'])) {
                return $response['variants'];
            }

            return null;
        }
    }

    public function getWooOrder($order_id)
    {
        $response = $this->wooApi("get", "order/" . $order_id);

        if (empty($response['error'])) {
            return $response['order'];
        }

        return null;
    }

    public function pullCustomer($customer_id)
    {
        try {
            $response = $this->wooApi("get", "customer/" . $customer_id);

            if (empty($response['error']) && !empty($response['customer'])) {
                $customer = $response['customer'];

                $model = Customer::updateOrCreate([
                    'user_id' => $this->id,
                    'wc_user_id' => $customer['id'],
                ], [
                    'email' => $customer['email'],
                    'name' => $customer['name'],
                    'password' => \Str::password(8)
                ]);

                $model->save();

                return $model;
            }

            return null;
        } catch (\Exception $exception) {
            report($exception);

            return null;
        }
    }

    public function pullOrder($order_id)
    {
        try {
            $response = $this->wooApi("get", "order/" . $order_id);

            if (empty($response['error'])) {
                $order = $response['order'];

                if ($order) {
                    return $this->storeWooOrder($order);
                }

            }

            return null;
        } catch (\Exception $exception) {
            report($exception);

            return null;
        }
    }

    public function storeWooOrder($order)
    {
        $existingOrder = Order::where('user_id', $this->id)
            ->where('wc_order_id', $order['id'])
            ->first();

        if ($existingOrder) {
            return $existingOrder;
        }

        $customer = Customer::updateOrCreate([
            'email' => $order['customer']['email'],
            'user_id' => $this->id,
        ], [
            'wc_user_id' => $order['customer']['id'],
            'name' => $order['customer']['display_name'] ?? '',
            'phone' => $order['customer']['phone'] ?? null,
            'password' => \Str::password(8)
        ]);

        $customer->save();

        $design_ids = [];
        $total_commission = 0;
        $total_price = 0;

        foreach ($order['items'] as $key => $item) {
            $meta_data = $item['item_data']['meta_data'];

            $design_id = null;
            foreach ($meta_data as $meta_item) {
                if ($meta_item['key'] === 'gs_design_id') {
                    $design_id = $meta_item['value'];
                    if ($design_id) {
                        $order['items'][$key]['design_id'] = $design_id;
                        $design_ids[] = $design_id;
                        break;
                    }
                }
            }

            $total_price += $item['item_data']['subtotal'];

            if ($design_id) {
                $design_id = str_replace('\r\n', '', $design_id);
                $design = Design::find($design_id);
                if ($design) {
                    $total_commission += $design->commission;
                    if ($item['item_data']['quantity'] ?? null) {
                        $design->quantity = $item['item_data']['quantity'];
                        $design->save();
                    }
                }
            }
        }

        $shippingAddress = $order['customer']['shipping_address'];

        $order = Order::create([
            'user_id' => $this->id,
            'customer_id' => $customer->id,
            'wc_order_id' => $order['id'],
            'data' => $order,
            'name' => $shippingAddress['first_name'] . ' ' . $shippingAddress['last_name'],
            'email' => $shippingAddress['email'],
            'phone' => $shippingAddress['phone'] ?? null,
            'status' => Design::STATUS_PROCESSING,
            'commission' => min($total_commission, $this->max_order),
            'price' => $total_price,
            'state' => $shippingAddress['state'] ?? null,
            'city' => $shippingAddress['city'] ?? null,
            'street' => $shippingAddress['address_1'] ?? null,
            'zipcode' => $shippingAddress['postcode'] ?? null
        ]);

        if (count($design_ids)) {
            Design::whereIn('id', $design_ids)->update([
                'order_id' => $order->id,
                'customer_id' => $customer->id
            ]);
        }

        foreach ($design_ids as $key => $design_id) {
            OutputGangSheet::dispatch($design_id)->delay(now()->addSeconds($key * 5));
        }

        return $order;
    }

    public function createWooCustomer($data)
    {
        $response = $this->wooApi("post", "customer", $data);

        if (empty($response['error'])) {
            return $response['user_id'];
        }

        return null;
    }

    private function saveProduct($product): Product
    {
        $productModel = Product::updateOrCreate([
            'user_id' => $this->id,
            'woo_product_id' => $product['id']
        ], [
            'title' => $product['title'],
            'redirect_url' => $this->website . '/cart',
            'button' => [
                'text' => $product['btnLabel'] ?? 'Build your own Gang Sheet',
            ],
            'type' => $product['artBoardType'] ?? 1,
        ]);

        $productModel->save();

        $variants = $product['variants'] ?? [];
        $current_variant_ids = [];

        if ($variants) {
            foreach ($variants as $variant) {
                $sizeModel = $productModel->sizes()->updateOrCreate([
                    'woo_variant_id' => $variant['id']
                ], [
                    'label' => $variant['title'],
                    'width' => floatval($variant['width']),
                    'height' => floatval($variant['height']),
                    'unit' => $variant['unit'],
                    'price' => floatval($variant['price']),
                    'max_allowed_files' => empty($variant['maxAllowedFileCount']) ? -1 : intval($variant['maxAllowedFileCount'])
                ]);

                $sizeModel->save();
                $current_variant_ids[] = $variant['id'];
            }
        }

        // Delete any variants that aren't in the current list
        if (!empty($current_variant_ids)) {
            $productModel->sizes()
                ->whereNotIn('woo_variant_id', $current_variant_ids)
                ->delete();
        }

        return $productModel->load('sizes');
    }

    public function cacheProducts($product_id = null): array
    {
        try {
            if ($product_id) {
                $product = $this->getWooProduct($product_id);

                return $product ? [$product] : [];
            } else {
                $products = $this->getWooProducts();

                $cachedProducts = [];
                if ($products) {
                    foreach ($products as $product) {
                        $cachedProduct = $this->saveProduct($product);
                        $cachedProduct['tags'] = $product['tags'] ?? [];
                        $cachedProduct['image'] = $product['image'] ?? [];
                        $cachedProducts[] = $cachedProduct;
                    }
                }

                return $cachedProducts;
            }
        } catch (\Exception $exception) {
            info($exception->getMessage());
            info("Failed to cache products for shop {$this->id}");

            return [];
        }
    }
}
