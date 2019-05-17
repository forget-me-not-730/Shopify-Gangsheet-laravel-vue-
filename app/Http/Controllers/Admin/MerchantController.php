<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelQueryBuilderInertiaJs\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MerchantController extends Controller
{

    function index(Request $request)
    {
        $perPage = $request->input('perPage') ?? 10;
        $type = $request->input('type', 'all');
        $search = $request->input('search', '');

        $merchantQuery = User::withTrashed()
            ->where('type', '!=', 'admin')
            ->withCount(['products', 'orders', 'designs']);

        if ($type !== 'all') {
            $merchantQuery->where('type', $type);
        }

        if ($search) {
            $merchantQuery->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('shop_uuid', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('company_name', 'LIKE', "%{$search}%")
                    ->orWhere('website', 'LIKE', "%{$search}%");
            });
        }

        $merchants = $merchantQuery->latest()
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Admin/Merchants', [
            'merchants' => $merchants,
            'filters' => [
                'type' => $type,
                'search' => $search
            ]
        ]);
    }

    public function create(Request $request)
    {
        try {
            $data = $this->validate($request, [
                'name' => 'nullable|string',
                'email' => 'required|email'
            ]);

            [$firstName, $lastName] = array_pad(explode(' ', $data['name']), 2, '');

            User::create([
                'shop_uuid' => Str::uuid(),
                'name' => $data['name'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company_name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
                'type' => 'custom',
                'status' => 'active'
            ]);

            return redirect()->back()->with('success', 'Merchant created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    function updateMerchant(Request $request)
    {
        $id = $request->get('id');
        $data = $this->validate($request, [
            'id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'company_name' => 'required|string',
            'slug' => 'required|string|regex:/^[a-z1-9\-]+$/|min:4|unique:users,slug,' . $id,
            'website' => 'required|string',
            'max_order' => 'required|numeric',
            'commission_rate' => 'required|numeric',
            'credits' => 'required|numeric',
            'status' => 'required|string',
            'galleryShareWith' => 'nullable|numeric'
        ]);

        $user = User::findOrFail($data['id']);

        $user->update([
            'name' => $data['first_name'] . ' ' . ($data['last_name'] ?? ''),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'company_name' => $data['company_name'],
            'slug' => $data['slug'],
            'website' => $data['website'],
            'max_order' => $data['max_order'],
            'commission_rate' => $data['commission_rate'],
            'credits' => $data['credits'],
            'status' => $data['status'],
        ]);

        $user->setSetting([
            'galleryShareWith' => $data['galleryShareWith'] ?? null
        ]);

        return redirect()->back();
    }

    function merchantProducts($id, Request $request)
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query->orWhere('title', 'LIKE', "%{$value}%")
                        ->orWhere('slug', 'LIKE', "%{$value}%")
                        ->orWhere('redirect_url', 'LIKE', "%{$value}%");
                });
            });
        });

        $perPage = $request->input('perPage') ?? 10;

        $products = QueryBuilder::for(Product::class)
            ->allowedFields(['id', 'title', 'slug', 'redirect_url', 'created_at', 'deleted_at',])
            ->allowedSorts(['id', 'title', 'slug', 'redirect_url', 'created_at', 'sizes_count', 'orders_count', 'deleted_at'])
            ->allowedFilters(['id', 'title', 'slug', 'redirect_url', 'created_at', 'deleted_at', $globalSearch])
            ->where('user_id', $id)
            ->withTrashed()
            ->withCount(['sizes', 'orders'])
            ->with(['sizes'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return inertia('Admin/MerchantProducts', [
            'products' => $products,
            'user_id' => $id
        ])->table(function (InertiaTable $table) {
            $table
                ->withGlobalSearch()
                ->defaultSort('-id')
                ->perPageOptions([10, 25, 50, 100])
                ->column(key: 'id', label: "ID", sortable: true, searchable: true)
                ->column(key: 'title', label: "Title", sortable: true, searchable: true)
                ->column(key: 'slug', label: "Code", sortable: true, searchable: true)
                ->column(key: 'redirect_url', label: "redirect_url", sortable: true, searchable: true)
                ->column(key: 'sizes_count', label: "Sizes", sortable: true)
                ->column(key: 'orders_count', label: "Orders", sortable: true)
                ->column(key: 'deleted_at', label: "Status", sortable: true, searchable: true)
                ->column(key: 'created_at', label: "Created At", sortable: true, searchable: true)
                ->column(key: 'actions', label: "Actions",);
        });
    }

    function impersonate(User $merchant)
    {
        $admin = auth()->user();
        session()->put('admin_id', $admin->id);
        auth()->login($merchant);
        return redirect()->to(route('merchant.dashboard.index'));
    }
}
