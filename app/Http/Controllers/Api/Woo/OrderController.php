<?php

namespace App\Http\Controllers\Api\Woo;

use App\Http\Controllers\Controller;
use App\Models\Design;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    public function create(Request $request)
    {
        try {
            $shop = $request->user();
            $data = $request->order;

            $order = $shop->storeWooOrder($data);
            return response()->json([
                'success' => true,
                'order_id' => $order->id
            ]);
        } catch (\Exception $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function getOrders(Request $request)
    {
        try {
            $data = $request->validate([
                'page' => 'nullable|numeric',
                'perPage' => 'nullable|numeric',
                'search' => 'nullable|string',
                'searchBy' => 'nullable|string',
                'sort' => 'nullable|numeric'
            ]);

            $searchBy = !empty($data['searchBy']) ? $data['searchBy'] : 'design_id'; 

            $shop = $request->user();

            $query = Design::select(['id', 'user_id', 'order_id', 'product_id', 'status'])
                ->where('user_id', $shop->id)
                ->whereNotNull('order_id');

            if (isset($data['sort'])) {
                if ($data['sort'] > 0) {
                    $query = $query->orderBy('order_id');
                } else if ($data['sort'] < 0) {
                    $query = $query->orderByDesc('order_id');
                }
            }

            $query = $query->with(['order', 'product']);

            $designs = $query->when($data['search'] ?? false, function ($query) use ($data, $searchBy) {
                $search = '%' . $data['search'] . '%';

                switch ($searchBy) {
                    case 'design_id':
                        $query->where('id', 'like', $search);
                        break;
                    case 'customer':
                        $query->whereHas('order', function ($query) use ($search) {
                            $query->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                        break;
                    case 'product':
                        $query->whereHas('product', function ($query) use ($search) {
                            $query->where('title', 'like', $search);
                        });
                        break;
                    default:
                        $query->where('id', 'like', $search);
                        break;
                }
            
                return $query;
            })
            ->latest()
            ->paginate(perPage: $data['perPage'] ?? 10, page: $data['page'] ?? 1)
            ->onEachSide(1);

            $designs->getCollection()
                ->withoutAppends()
                ->transform(function ($design) {
                    $design['design_id'] = $design['id'];

                    if ($order = $design['order'] ?? null) {
                        $design['customer'] = [
                            'name' => $order['name'],
                            'email' => $order['email'],
                        ];

                        $design['wc_order_id'] = $order['wc_order_id'];
                        $design['ordered_at'] = $order['created_at'];

                        if (isset($order['data']['items'])) {
                            $orderItems = $order['data']['items'];
                            foreach ($orderItems as $item) {
                                if ($item['design_id'] == $design['id']) {
                                    $design['product'] = $item['product'];
                                    $design['variant'] = $item['variant'] ?? null;
                                    $design['subtotal'] = $item['item_data']['subtotal'] ?? null;
                                    $design['quantity'] = $item['item_data']['quantity'] ?? null;
                                    break;
                                }
                            }
                        }
                    }

                    return $design;
                });

            return response()->json([
                'success' => true,
                'orders' => $designs->items(),
                'current_page' => $designs->currentPage(),
                'total' => $designs->total(),
                'links' => $designs->linkCollection()
            ]);

        } catch (\Exception $exception) {

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }

    }
}
