<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'today_revenue' => Order::where('payment_status', 'paid')
                ->whereDate('created_at', Carbon::today())
                ->sum('total_amount'),
            'this_month_revenue' => Order::where('payment_status', 'paid')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('total_amount'),
        ];

        // Recent orders
        $recentOrders = Order::with(['user', 'items.product'])
            ->latest()
            ->limit(10)
            ->get();

        // Recent users
        $recentUsers = User::latest()
            ->limit(10)
            ->get();

        // Top products
        $topProducts = Product::withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->limit(10)
            ->get();

        return view('pages.admin.dashboard', compact('stats', 'recentOrders', 'recentUsers', 'topProducts'));
    }
}