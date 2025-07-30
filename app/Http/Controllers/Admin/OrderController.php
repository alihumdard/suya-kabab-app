<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(10);
        return view('pages.admin.order.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.addons']);
        return view('pages.admin.order.show', compact('order'));
    }

    public function destroy(Order $order)
    {
        try {
            // Delete related order items and their addons first
            $order->items()->each(function($item) {
                $item->addons()->detach();
                $item->delete();
            });
            
            // Then delete the order
            $order->delete();
            
            return redirect()->route('admin.orders.index')
                ->with('success', 'Order deleted successfully.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting order: ' . $e->getMessage());
        }
    }

    /**
     * Update the order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,dispatched,rejected,completed,cancelled'
        ]);

        try {
            $order->update([
                'status' => $request->status,
                // Update delivered_at timestamp if order is marked as completed
                'delivered_at' => $request->status === 'completed' ? now() : $order->delivered_at
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'status' => $order->status,
                'status_label' => ucfirst($order->status)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ], 500);
        }
    }
}
