<?php

namespace App\Http\Controllers\Customer;

use App\Enums\Queue;
use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Jobs\OutputGangSheet;
use App\Mail\CustomerNewOrderEmail;
use App\Mail\MerchantNewOrderEmail;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CartController extends Controller
{
    function index($slug, Request $request)
    {
        $merchant = $request->get('shop');

        $product = Product::where('slug', $slug)->where('user_id', $merchant->id)->first();
        if (!$product) {
            return inertia('Customer/InvalidProduct');
        }

        $cart_key = 'cart:' . $product->id;
        $cart = session()->get($cart_key) ?? [];
        $designs = Design::with(['product:id,title,type', 'size:id,label,price,height'])
            ->select('id', 'user_id', 'product_id', 'size_id', 'quantity')
            ->whereIn('id', $cart)->get();

        return inertia('Customer/CartPage', [
            'merchant' => $merchant,
            'designs' => $designs,
            'product' => $product
        ]);
    }

    function add(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array'
        ]);

        $ids = $data['ids'];

        if (count($ids) > 0) {
            foreach ($ids as $design_id) {

                $design = Design::find($design_id);

                if ($design) {
                    $cart_key = 'cart:' . $design->product_id;
                    $cart = session()->get($cart_key);

                    if (empty($cart)) {
                        $cart = [];
                    }

                    if (!in_array($design_id, $cart)) {
                        $cart[] = $design_id;
                    }

                    session()->put($cart_key, $cart);
                }

            }

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => true,
                'error' => 'One or more designs are required.'
            ]);
        }
    }

    function updateQuantity(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'required|string',
            'quantity' => 'required|integer',
        ]);

        $design = Design::findOrFail($data['id']);
        $design->update([
            'quantity' => $data['quantity']
        ]);

        return response()->json([
            'quantity' => $data['quantity']
        ]);
    }

    function delete($id)
    {
        $design = Design::findOrFail($id);
        $cart_key = 'cart:' . $design->product_id;
        $cart = session()->get($cart_key);
        if ($cart) {
            if (($key = array_search($id, $cart)) !== false) {
                unset($cart[$key]);
                session()->put($cart_key, $cart);
            }
        }

        return response()->json();
    }

    function checkout(Request $request)
    {
        $data = $this->validate($request, [
            'product_id' => 'required|integer',
            'customer_id' => 'nullable|integer',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'street' => 'nullable|string',
            'zipcode' => 'nullable|string',
        ]);

        $merchant = $request->get('shop');

        $cart_key = 'cart:' . $data['product_id'];
        $cart = session()->get($cart_key) ?? [];
        $total_price = 0;
        $total_commission = 0;
        $designs = Design::with('merchant', 'size')->whereIn('id', $cart)->get();

        if ($designs->isEmpty()) {
            return redirect()->back()->withErrors(['message' => 'Cart is empty']);
        }

        foreach ($designs as $key => $design) {
            $total_price += $design->size->price;
            $total_commission += $design->commission;

            OutputGangSheet::dispatch($design->id)->delay(now()->addSeconds($key * 5));
        }

        $order = Order::create([
            'user_id' => $merchant->id,
            'customer_id' => $data['customer_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => 'created',
            'commission' => min($total_commission, $merchant->max_order),
            'price' => $total_price,
            'state' => $data['state'] ?? null,
            'city' => $data['city'] ?? null,
            'street' => $data['street'] ?? null,
            'zipcode' => $data['zipcode'] ?? null
        ]);

        Design::whereIn('id', $cart)->update([
            'order_id' => $order->id,
        ]);

        session()->remove($cart_key);

        Mail::to($order->email)->send(new CustomerNewOrderEmail($order->id));
        Mail::to($merchant->email)->send(new MerchantNewOrderEmail($order->id));
        event(new OrderCreated($order));

        $product = Product::findOrFail($data['product_id']);

        return response()->json([
            'success' => true,
            'redirect_url' => $product->redirect_url
        ]);
    }
}
