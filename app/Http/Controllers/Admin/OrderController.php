<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $validated = $request->validate([
            'status' => 'required|string|in:pending,dispatched,rejected,completed,cancelled'
        ]);

        \Log::info('Validation successful.', ['validated_data' => $validated]);

        try {
            DB::beginTransaction();

            $updateData = ['status' => $validated['status']];
            
            if ($validated['status'] === 'completed') {
                $updateData['delivered_at'] = now();
            }
            
            $order->update($updateData);
            $order->refresh();

            DB::commit();

            \Log::info('Order status updated successfully in the database.', ['order_id' => $order->id, 'new_status' => $order->status]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'status' => $order->status,
                'status_label' => ucfirst($order->status)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating order status: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status. Please try again.'
            ], 500);
        }
    }
}
