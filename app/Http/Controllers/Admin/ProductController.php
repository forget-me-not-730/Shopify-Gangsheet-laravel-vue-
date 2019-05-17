<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use ProtoneMedia\LaravelQueryBuilderInertiaJs\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    function index(Request $request)
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query
                        ->orWhere('title', 'LIKE', "%{$value}%")
                        ->orWhere('redirect_url', 'LIKE', "%{$value}%")
                        ->orWhere('created_at', 'LIKE', "%{$value}%");
                });
            });
        });

        $perPage = $request->input('perPage') ?? 10;

        $products = QueryBuilder::for(Product::class)
            ->allowedSorts(['id', 'title', 'code', 'status', 'created_at'])
            ->allowedFilters(['title', 'created_at', 'status', $globalSearch])
            ->with('user')
            ->withCount(['sizes', 'orders'])
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Admin/Products', [
            'products' => $products
        ])->table(function (InertiaTable $table) {
            $table
                ->withGlobalSearch()
                ->defaultSort('id')
                ->perPageOptions([10, 25, 50, 100])
                ->column(key: 'id', label: "ID", sortable: true, searchable: true)
                ->column(key: 'title', label: "Title", sortable: true, searchable: true)
                ->column(key: 'slug', label: "Code", sortable: true, searchable: true)
                ->column(key: 'redirect_url', label: "redirect_url", sortable: true, searchable: true)
                ->column(key: 'sizes_count', label: "Sizes", sortable: true)
                ->column(key: 'orders_count', label: "Orders", sortable: true)
                ->column(key: 'deleted_at', label: "Status", sortable: true, searchable: true)
                ->column(key: 'created_at', label: "Created At", sortable: true, searchable: true)
                ->column(key: 'actions', label: "Actions");
        });
    }

}
