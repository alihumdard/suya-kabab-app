<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'transaction_id',
        'reference',
        'amount',
        'currency',
        'payment_method',
        'status',
        'gateway_response',
        'gateway_data',
        'card_last4',
        'card_brand',
        'card_holder_name',
        'payment_channel',
        'ip_address',
        'user_agent',
        'paid_at',
        'failed_at',
        'failure_reason',
        'meta_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_data' => 'array',
        'meta_data' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that owns the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the refunds for this payment.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Check if payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this->status === 'successful' &&
            $this->payment_method !== 'cod' &&
            $this->refunds()->where('status', 'successful')->sum('amount') < $this->amount;
    }

    /**
     * Get the refundable amount.
     */
    public function getRefundableAmount(): float
    {
        $refundedAmount = $this->refunds()->where('status', 'successful')->sum('amount');
        return max(0, $this->amount - $refundedAmount);
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccessful(array $gatewayData = []): void
    {
        $this->update([
            'status' => 'successful',
            'gateway_data' => $gatewayData,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
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
     * Mark payment as refunded.
     */
    public function markAsRefunded(): void
    {
        $this->update(['status' => 'refunded']);
    }

    /**
     * Create a refund for this payment.
     */
    public function createRefund(float $amount, string $reason = 'Customer request'): Refund
    {
        if (!$this->canBeRefunded()) {
            throw new \Exception('Payment cannot be refunded');
        }

        if ($amount > $this->getRefundableAmount()) {
            throw new \Exception('Refund amount exceeds refundable amount');
        }

        return $this->refunds()->create([
            'user_id' => $this->user_id,
            'payment_id' => $this->id,
            'order_id' => $this->order_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
            'reference' => 'REFUND_' . time() . '_' . $this->id,
        ]);
    }

    /**
     * Get payment status for display.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'successful' => 'Successful',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => 'Unknown'
        };
    }

    /**
     * Get payment method for display.
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match ($this->payment_method) {
            'card' => 'Credit/Debit Card',
            'cod' => 'Cash on Delivery',
            'flutterwave' => 'Flutterwave',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
            default => 'Unknown'
        };
    }
}



