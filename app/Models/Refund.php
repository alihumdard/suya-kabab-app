<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_id',
        'order_id',
        'reference',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'reason',
        'gateway_response',
        'gateway_data',
        'processed_by',
        'processed_at',
        'failed_at',
        'failure_reason',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_data' => 'array',
        'meta_data' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment that owns the refund.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the order that owns the refund.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark refund as successful.
     */
    public function markAsSuccessful(array $gatewayData = []): void
    {
        $this->update([
            'status' => 'successful',
            'gateway_data' => $gatewayData,
            'processed_at' => now(),
        ]);

        // Update payment status if fully refunded
        $payment = $this->payment;
        if ($payment->getRefundableAmount() <= 0) {
            $payment->markAsRefunded();
        }

        // Update order status based on refund
        $order = $this->order;
        if ($order) {
            $order->updateRefundStatus();
        }
    }

    /**
     * Mark refund as failed.
     */
    public function markAsFailed(string $reason, array $gatewayData = []): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'gateway_data' => $gatewayData,
            'failed_at' => now(),
        ]);
    }

    /**
     * Mark refund as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * Mark refund as cancelled.
     */
    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get refund status for display.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'successful' => 'Successful',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Check if refund can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if refund is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }

    /**
     * Check if refund is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
