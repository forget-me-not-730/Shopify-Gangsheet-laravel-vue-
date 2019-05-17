<?php

namespace App\Http\Controllers\Api\Woo;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function getPayments(Request $request)
    {
        try {
            $data = $request->validate([
                'page'   => 'nullable|numeric',
                'search' => 'nullable|string'
            ]);

            $user = $request->user();

            $query = Transaction::where('user_id', $user->id);

            $query = $query->latest()
                ->paginate(perPage: 15, page: $data['page'] ?? 1)
                ->onEachSide(1);

            return response()->json([
                'success'      => true,
                'transactions' => $query->items(),
                'current_page' => $query->currentPage(),
                'total'        => $query->total(),
                'links'        => $query->linkCollection()
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error'   => $exception->getMessage()
            ]);
        }
    }
}
