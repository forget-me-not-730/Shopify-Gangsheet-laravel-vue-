<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Mail\CustomerCreationEmail;
use App\Mail\CustomerPasswordChangeEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelQueryBuilderInertiaJs\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Customer;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    function index(Request $request)
    {
        $user = $request->user();
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query->where('name', 'LIKE', "%{$value}%")
                        ->orWhere('email', 'LIKE', "%{$value}%");
                });
            });
        });

        $perPage = $request->input('perPage') ?? 10;
        $status = $request->input('status') ?? 'all';

        $query = QueryBuilder::for(Customer::class)
            ->allowedFields(['id', 'name', 'email', 'phone', 'created_at'])
            ->allowedSorts(['id', 'name', 'email', 'phone', 'created_at'])
            ->allowedFilters(['id', 'name', 'created_at', $globalSearch]);

        $query->where('user_id', $user->id);

        $customers = $query->latest()
            ->paginate($perPage)
            ->withQueryString();

        $user = auth()->user();

        return inertia('Merchant/Customers', [
            'customers' => $customers,
            'status' => $status,
            'user' => $user,
        ])->table(function (InertiaTable $table) use ($user) {
            $table
                ->withGlobalSearch()
                ->defaultSort('-id')
                ->perPageOptions([10, 25, 50, 100])
                ->column(key: 'id', label: "ID", sortable: true, searchable: true)
                ->column(key: 'name', label: "Name")
                ->column(key: 'email', label: "Email", sortable: true, searchable: true)
                ->column(key: 'phone', label: "Phone", sortable: true, searchable: true)
                ->column(key: 'created_at', label: "Created At", sortable: true, searchable: true);

            if ($user->type != 'woo') {
                $table->column(key: 'actions', label: "Actions");
            }
        });
    }

    function saveCustomer(Request $request)
    {
        $data = $this->validate($request, [
            'id' => 'nullable|integer',
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'email' => [
                'required',
                'string',
                'email',
                Rule::unique('customers')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user_id);
                })->ignore($request->id),
            ],
            'phone' => 'nullable|string',
            'type' => 'nullable|string',
            'is_email_send' => 'boolean',
            'password' => 'nullable|string',
            'confirm_password' => 'nullable|string|same:password|required_with:password',
            'is_password_email_send' => 'boolean',
        ]);

        // Generate random password only if no password is provided
        $password = null;
        if (!$data['password']) {
            $password = Str::random(12);
        }

        // Create customer on WooCommerce site
        if ($data['type'] == "woo") {
            $wooCustomerData = [
                'name' => $data['name'],
                'password' => $data['password'] ?? $password,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ];

            $wooCustomerId = auth()->user()->createWooCustomer($wooCustomerData);
            $customer = auth()->user()->pullCustomer($wooCustomerId);
        } else {
            if (isset($data['id'])) {
                $customer = Customer::findOrFail($data['id']);
                // Update password only if new password is provided
                if ($data['password']) {
                    $customer->password = Hash::make($data['password']);
                }
            } else {
                $customer = new Customer();
                $customer->password = Hash::make($data['password'] ?? $password);
                $customer->user_id = $data['user_id'] ?? auth()->user()->id;
            }

            $customer->name = $data['name'];
            $customer->email = $data['email'];
            $customer->phone = $data['phone'] ?? null;
            $customer->save();
        }

        // Send creation email if requested
        if ($data['is_email_send'] && $customer) {
            Mail::to($customer->email)->send(new CustomerCreationEmail(
                $customer->id,
                !isset($data['id']),
                $data['password'] ?? $password
            ));
        }

        // Send password change email if requested
        if ($data['is_password_email_send'] && $customer) {
            Mail::to($customer->email)->send(new CustomerPasswordChangeEmail(
                $customer->id,
                $data['password'] ?? $password
            ));
        }

        return redirect()->back()->with('password', $data['password'] ?? $password);
    }
}
