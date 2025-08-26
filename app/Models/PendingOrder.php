<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PendingOrder extends Model
{
    protected $fillable = [
        'user_id',
        'payment_reference',
        'order_data',
        'status',
        'total_amount',
        'payment_method',
        'expires_at',
        'payment_verified_at',
        'order_created_at',
        'order_id',
        'notes'
    ];

    protected $casts = [
        'order_data' => 'array',
        'total_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'payment_verified_at' => 'datetime',
        'order_created_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scopes
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopeForReference($query, $reference)
    {
        return $query->where('payment_reference', $reference);
    }

    /**
     * Helper methods
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function markAsPaymentVerified(): void
    {
        $this->update([
            'status' => 'payment_verified',
            'payment_verified_at' => now()
        ]);
    }

    public function markAsOrderCreated(Order $order): void
    {
        $this->update([
            'status' => 'order_created',
            'order_id' => $order->id,
            'order_created_at' => now()
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason
        ]);
    }

    /**
     * Create a new pending order
     */
    public static function createForPayment(array $orderData, string $reference, int $userId, int $expiryHours = 2): self
    {
        return self::create([
            'user_id' => $userId,
            'payment_reference' => $reference,
            'order_data' => $orderData,
            'total_amount' => $orderData['total_amount'],
            'payment_method' => $orderData['payment_method'] ?? 'card',
            'expires_at' => now()->addHours($expiryHours),
            'status' => 'pending_payment'
        ]);
    }

    /**
     * Clean up expired pending orders
     */
    public static function cleanupExpired(): int
    {
        return self::expired()
            ->whereIn('status', ['pending_payment', 'payment_verified'])
            ->update(['status' => 'expired']);
    }
}
