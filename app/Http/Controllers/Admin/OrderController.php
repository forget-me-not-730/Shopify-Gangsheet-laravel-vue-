<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use ProtoneMedia\LaravelQueryBuilderInertiaJs\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query
                        ->orWhere('name', 'LIKE', "%{$value}%")
                        ->orWhere('phone', 'LIKE', "%{$value}%")
                        ->orWhere('email', 'LIKE', "%{$value}%");
                });
            });
        });

        $perPage = $request->input('perPage') ?? 10;

        $orders = QueryBuilder::for(Order::class)
            ->allowedFields(['id', 'name', 'email', 'phone', 'price', 'status', 'created_at'])
            ->allowedSorts(['id', 'name', 'email', 'phone', 'price', 'product_title', 'designs_count', 'status', 'created_at'])
            ->allowedFilters(['id', 'name', 'email', 'phone', 'created_at', 'status', $globalSearch])
            ->withAggregate('product', 'title')
            ->withAggregate('user', 'company_name')
            ->withCount('designs')
            ->with([
                'designs' => function ($q) {
                    $q->withTrashed()->with('product', 'size');
                }
            ])
            ->withTrashed()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Orders', [
            'orders' => $orders
        ])->table(function (InertiaTable $table) {
            $table
                ->withGlobalSearch()
                ->defaultSort('-id')
                ->perPageOptions([10, 25, 50, 100])
                ->column(key: 'id', label: "ID", sortable: true, searchable: true)
                ->column(key: 'user_company_name', label: "Merchant", sortable: true, )
                ->column(key: 'product_title', label: "Product", sortable: true, )
                ->column(key: 'name', label: "Name", sortable: true, searchable: true)
                ->column(key: 'email', label: "Email", sortable: true, searchable: true)
                ->column(key: 'phone', label: "Phone", sortable: true, searchable: true)
                ->column(key: 'designs_count', label: "Items", sortable: true, )
                ->column(key: 'price', label: "Total Price")
                ->column(key: 'commission', label: "Commission", sortable: true, searchable: true)
                ->column(key: 'status', label: "Status", sortable: true, searchable: true)
                ->column(key: 'created_at', label: "Created At", sortable: true, searchable: true)
                ->column(key: 'actions', label: "Actions");
        });
    }


    public function designs($order_id)
    {
        $order = Order::with(['user', 'customer'])->findOrFail($order_id);

        $design = Design::where('order_id', $order_id)->firstOrFail();

        $design->load([
            'product' => function ($q) {
                $q->with('sizes');
            },
            'size'
        ]);

        if ($order->user->isWooStore()) {
            $product = $order->user->getWooProduct($design->product_id);
        } else {
            $product = $design->product;
        }

        $designs = Design::where('order_id', $order_id)->get();
        $workingDesigns = [];

        foreach ($designs as $design) {
            $workingDesigns[] = $design->data;
        }

        $props = [
            'id' => $design->id,
            'product' => $product,
            'size' => $design->data['meta']['variant'] ?? $design->size,
            'merchant' => $order->user,
            'order' => $order,
            'workingDesigns' => $workingDesigns,
            'customer' => $order->customer,
            'quantity' => $design->quantity,
            'newDesign' => false,
            'admin' => true
        ];

        if ($product->isRollingGangSheet()) {
            return inertia('Customer/RollingGangSheetPage', $props);
        }

        return inertia('Customer/BuilderPage', $props);
    }
}
