<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Order;
use App\Models\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    function index(Request $request)
    {
        $merchant = $request->user();

        if ($merchant->isCustomStore()) {
            $total_orders = Design::where('user_id', $merchant->id)
                ->count();
            $month_orders = Design::where('user_id', $merchant->id)
                ->whereMonth('created_at', now())
                ->count();
            $today_orders = Design::where('user_id', $merchant->id)
                ->whereDate('created_at', now())
                ->count();
        } else {
            $total_orders = Order::where('user_id', $merchant->id)
                ->count();
            $month_orders = Order::where('user_id', $merchant->id)
                ->whereMonth('created_at', now())
                ->count();
            $today_orders = Order::where('user_id', $merchant->id)
                ->whereDate('created_at', now())
                ->count();
        }

        return inertia('Merchant/Dashboard', [
            'stats' => [
                'total_orders' => $total_orders,
                'month_orders' => $month_orders,
                'today_orders' => $today_orders,
                'credits' => $merchant->credits,
            ]
        ]);
    }

    public function api(Request $request)
    {
        $merchant = $request->user();
        $merchant->load(['tokens' => function ($query) {
            $query->where('name', '!=', 'woo_access_token');
        }]);

        return inertia('Merchant/CustomApi', [
            'merchant' => $merchant,
        ]);
    }

    function data(Request $request)
    {
        $merchant = $request->user();

        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $now = Carbon::now();

        if ($merchant->isCustomStore()) {
            $newOrders = Design::select(\DB::raw('Date(created_at) as date'), \DB::raw('count(*) as count'))
                ->where('user_id', $merchant->id)
                ->whereBetween('created_at', [$thirtyDaysAgo, $now])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();
        } else {
            $newOrders = Order::select(\DB::raw('Date(created_at) as date'), \DB::raw('count(*) as count'))
                ->where('user_id', $merchant->id)
                ->whereBetween('created_at', [$thirtyDaysAgo, $now])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();
        }

        return response()->json([
            'newOrders' => [
                'labels' => $newOrders->pluck('date'),
                'data' => $newOrders->pluck('count'),
            ]
        ]);
    }

    public function createToken(Request $request)
    {
        try {
            $data = $request->validate([
                'token_name' => 'required|string',
            ]);

            $merchant = $request->user();

            $token = $merchant->createToken($data['token_name']);

            return response()->json([
                'success' => true,
                'token' => $token->plainTextToken,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteToken(Request $request)
    {
        try {
            $data = $request->validate([
                'token_id' => 'required|integer',
            ]);

            PersonalAccessToken::where('id', $data['token_id'])->delete();

            return response()->json([
                'success' => true,
                'message' => 'API token has been deleted',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file'
        ]);

        $image = $request->file('image');

        $fileName = Str::uuid();
        $extension = $image->getClientOriginalExtension();

        $imagePath = "images/$fileName.$extension";

        spaces()->put($imagePath, $image->getContent());

        $imageUrl = spaces()->url($imagePath);

        return response()->json([
            'success' => true,
            'url' => $imageUrl
        ]);
    }

    public function saveWebhooks(Request $request)
    {
        $data = $request->validate([
            'webhookGangSheetCompleted' => 'nullable|string'
        ]);

        $merchant = $request->user();

        $merchant->setSetting([
            'webhookGangSheetCompleted' => $data['webhookGangSheetCompleted'] ?? null
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}
