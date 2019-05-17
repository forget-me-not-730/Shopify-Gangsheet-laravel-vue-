<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Design;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $query = Design::select(['id', 'user_id', 'order_id', 'size_id', 'data', 'updated_at'])
            ->where('user_id', $user->id)
            ->where('paid', 1)
            ->with([
                'merchant:id,website,company_name,commission_rate',
                'size:id,width,height,unit',
                'order:id,name,email',
                'customer:id,name,email'
            ]);

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

        return inertia('Merchant/Transactions', [
            'transactions' => $transactions,
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ]
        ]);
    }
}
