<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\FlutterwavePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    protected $paymentService;

    public function __construct(FlutterwavePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Request a refund for an order
     */
    public function requestRefund(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found or access denied'
            ], 404);
        }

        // Get the latest payment for this order
        $payment = $order->latestPayment;

        if (!$payment) {
            return response()->json([
                'error' => true,
                'message' => 'No payment found for this order'
            ], 400);
        }

        if (!$payment->canBeRefunded()) {
            return response()->json([
                'error' => true,
                'message' => 'Payment cannot be refunded'
            ], 400);
        }

        $refundAmount = $request->amount ?? $payment->getRefundableAmount();

        if ($refundAmount > $payment->getRefundableAmount()) {
            return response()->json([
                'error' => true,
                'message' => 'Refund amount exceeds refundable amount'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Create refund record
            $refund = $payment->createRefund($refundAmount, $request->reason);

            // Process refund through payment gateway if not cash
            if ($payment->payment_method !== 'cash') {
                $refund->markAsProcessing();

                $result = $this->paymentService->processRefund(
                    $payment->id,
                    $refundAmount,
                    $request->reason
                );

                if ($result['success']) {
                    $refund->markAsSuccessful($result['data'] ?? []);

                    // Refresh order to get updated status
                    $order->refresh();

                    DB::commit();

                    return response()->json([
                        'error' => false,
                        'message' => 'Refund processed successfully',
                        'data' => [
                            'refund_id' => $refund->id,
                            'refund_reference' => $refund->reference,
                            'amount' => $refundAmount,
                            'status' => 'successful',
                            'transaction_id' => $result['data']['id'] ?? null,
                            'order_status' => [
                                'current_status' => $order->status,
                                'payment_status' => $order->payment_status,
                                'is_fully_refunded' => $order->isFullyRefunded(),
                                'is_partially_refunded' => $order->isPartiallyRefunded(),
                                'total_refunded' => $order->getTotalRefundedAmount(),
                                'refundable_amount' => $order->getRefundableAmount()
                            ]
                        ]
                    ], 200);
                } else {
                    $refund->markAsFailed($result['message']);

                    DB::commit();

                    return response()->json([
                        'error' => true,
                        'message' => 'Refund processing failed: ' . $result['message'],
                        'data' => [
                            'refund_id' => $refund->id,
                            'refund_reference' => $refund->reference,
                            'status' => 'failed',
                        ]
                    ], 400);
                }
            } else {
                // For cash payments, mark as successful immediately
                $refund->markAsSuccessful();

                // Refresh order to get updated status
                $order->refresh();

                DB::commit();

                return response()->json([
                    'error' => false,
                    'message' => 'Cash refund request submitted successfully',
                    'data' => [
                        'refund_id' => $refund->id,
                        'refund_reference' => $refund->reference,
                        'amount' => $refundAmount,
                        'status' => 'successful',
                        'note' => 'Cash refunds are processed manually by our team',
                        'order_status' => [
                            'current_status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'is_fully_refunded' => $order->isFullyRefunded(),
                            'is_partially_refunded' => $order->isPartiallyRefunded(),
                            'total_refunded' => $order->getTotalRefundedAmount(),
                            'refundable_amount' => $order->getRefundableAmount()
                        ]
                    ]
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund processing error: ' . $e->getMessage());

            return response()->json([
                'error' => true,
                'message' => 'Refund processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get refund status
     */
    public function getRefundStatus(Request $request): JsonResponse
    {
        $request->validate([
            'refund_id' => 'required|exists:refunds,id',
        ]);

        $user = Auth::user();
        $refund = Refund::where('id', $request->refund_id)
            ->where('user_id', $user->id)
            ->with(['payment', 'order'])
            ->first();

        if (!$refund) {
            return response()->json([
                'error' => true,
                'message' => 'Refund not found or access denied'
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Refund status retrieved successfully',
            'data' => [
                'refund' => [
                    'id' => $refund->id,
                    'reference' => $refund->reference,
                    'amount' => $refund->amount,
                    'status' => $refund->status,
                    'status_display' => $refund->status_display,
                    'reason' => $refund->reason,
                    'created_at' => $refund->created_at,
                    'processed_at' => $refund->processed_at,
                    'failure_reason' => $refund->failure_reason,
                ],
                'payment' => [
                    'id' => $refund->payment->id,
                    'payment_method' => $refund->payment->payment_method_display,
                    'original_amount' => $refund->payment->amount,
                    'refundable_amount' => $refund->payment->getRefundableAmount(),
                ],
                'order' => [
                    'id' => $refund->order->id,
                    'order_number' => $refund->order->order_number,
                ]
            ]
        ], 200);
    }

    /**
     * Get all refunds for a user
     */
    public function getUserRefunds(Request $request): JsonResponse
    {
        $user = Auth::user();

        $refunds = Refund::where('user_id', $user->id)
            ->with(['payment', 'order'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'error' => false,
            'message' => 'Refunds retrieved successfully',
            'data' => [
                'refunds' => $refunds->items(),
                'pagination' => [
                    'current_page' => $refunds->currentPage(),
                    'last_page' => $refunds->lastPage(),
                    'per_page' => $refunds->perPage(),
                    'total' => $refunds->total(),
                ]
            ]
        ], 200);
    }

    /**
     * Get refund history for an order
     */
    public function getOrderRefunds(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = Auth::user();
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found or access denied'
            ], 404);
        }

        $refunds = $order->refunds()
            ->with(['payment'])
            ->latest()
            ->get();

        return response()->json([
            'error' => false,
            'message' => 'Order refunds retrieved successfully',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refunds' => $refunds->map(function ($refund) {
                    return [
                        'id' => $refund->id,
                        'reference' => $refund->reference,
                        'amount' => $refund->amount,
                        'status' => $refund->status,
                        'status_display' => $refund->status_display,
                        'reason' => $refund->reason,
                        'created_at' => $refund->created_at,
                        'processed_at' => $refund->processed_at,
                        'payment_method' => $refund->payment->payment_method_display,
                    ];
                })
            ]
        ], 200);
    }

    /**
     * Cancel a pending refund
     */
    public function cancelRefund(Request $request): JsonResponse
    {
        $request->validate([
            'refund_id' => 'required|exists:refunds,id',
        ]);

        $user = Auth::user();
        $refund = Refund::where('id', $request->refund_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$refund) {
            return response()->json([
                'error' => true,
                'message' => 'Refund not found or access denied'
            ], 404);
        }

        if (!$refund->canBeProcessed()) {
            return response()->json([
                'error' => true,
                'message' => 'Refund cannot be cancelled'
            ], 400);
        }

        $refund->markAsCancelled('Cancelled by user');

        return response()->json([
            'error' => false,
            'message' => 'Refund cancelled successfully',
            'data' => [
                'refund_id' => $refund->id,
                'status' => 'cancelled'
            ]
        ], 200);
    }

    /**
     * Get order refund summary
     */
    public function getOrderRefundSummary(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = Auth::user();
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found or access denied'
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Order refund summary retrieved successfully',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
                'total_refunded' => $order->getTotalRefundedAmount(),
                'refundable_amount' => $order->getRefundableAmount(),
                'is_fully_refunded' => $order->isFullyRefunded(),
                'is_partially_refunded' => $order->isPartiallyRefunded(),
                'can_be_refunded' => $order->canBeRefunded(),
                'refunds_count' => $order->refunds()->count(),
                'successful_refunds_count' => $order->refunds()->where('status', 'successful')->count()
            ]
        ]);
    }
}
