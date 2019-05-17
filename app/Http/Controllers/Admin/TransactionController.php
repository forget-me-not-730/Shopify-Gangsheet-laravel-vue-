<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {

        $perPage = $request->input('perPage', 10);
        $user = $request->input('user');
        $search = $request->input('search');

        $users = User::select('id', 'website', 'company_name')
            ->where(function ($query) {
                $query->whereNotNull('website')
                    ->orWhereNotNull('company_name');
            })->get();

        $query = Design::select(['id', 'user_id', 'order_id', 'size_id', 'data', 'updated_at'])
            ->where('paid', 1)
            ->with([
                'merchant:id,website,company_name,commission_rate',
                'size:id,width,height,unit',
                'order:id,name,email',
                'customer:id,name,email'
            ]);

        if ($user) {
            $query->where('user_id', $user);
        }

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('order_id', 'like', "%" . trim($search) . "%")
                    ->orWhere('id', 'like', "%" . trim($search) . "%")
                    ->orWhere('data', 'like', "%" . trim($search) . "%");
            });
        }

        $transactions = $query->latest()
            ->paginate($perPage)
            ->withQueryString();

        $transactions->getCollection()->each->append('commission');

        return inertia('Admin/Transactions', [
            'users' => $users,
            'transactions' => $transactions,
            'filters' => [
                'user' => $user,
                'search' => $search,
                'perPage' => $perPage,
            ]
        ]);
    }
}
