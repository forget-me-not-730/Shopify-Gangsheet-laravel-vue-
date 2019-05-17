<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $orders = Order::count();
        $shops = User::count();
        $today_shops = User::whereDate('created_at', Carbon::today())->count();
        $revenue = Order::sum('commission');

        $stats = [
            'shops' => $shops,
            'orders' => $orders,
            'revenue' => round($revenue, 2),
            'today_shops' => $today_shops,
        ];

        return inertia('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }
    public function data()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $now = Carbon::now();

        $newMerchants = User::select(\DB::raw('Date(created_at) as date'), \DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$thirtyDaysAgo, $now])
            ->whereNotNull("password")
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $newOrders = Order::select(\DB::raw('Date(created_at) as date'), \DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$thirtyDaysAgo, $now])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        return response()->json([
            'newMerchants' => [
                'labels' => $newMerchants->pluck('date'),
                'data' => $newMerchants->pluck('count'),
            ],
            'newOrders' => [
                'labels' => $newOrders->pluck('date'),
                'data' => $newOrders->pluck('count'),
            ],
        ]);
    }
}
