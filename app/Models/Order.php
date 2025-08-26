<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Setting;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'payment_status',
        'payment_method',
        'payment_reference',
        'notes',
        'delivery_address',
        'delivery_phone',
        'delivery_instructions',
        'delivery_method', // pickup or delivery
        'estimated_delivery_time',
        'delivered_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'estimated_delivery_time' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the order reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the payments for this order.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the refunds for this order.
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get the latest payment for this order.
     */
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latest();
    }

    /**
     * Scope a query to only include orders with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Generate unique order number using UUID.
     */
    public static function generateOrderNumber()
    {
        return (string) Str::uuid();
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled()
    {
        return in_array($this->status, ['pending']);
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is for delivery.
     */
    public function isDelivery()
    {
        return $this->delivery_method === 'delivery';
    }

    /**
     * Check if order is for pickup.
     */
    public function isPickup()
    {
        return $this->delivery_method === 'pickup';
    }

    /**
     * Get delivery charges based on delivery method.
     */
    public function getDeliveryCharges()
    {
        if ($this->isPickup()) {
            return 0;
        }

        // Use dynamic delivery charges from settings
        return Setting::calculateDeliveryCharges($this->subtotal, $this->delivery_method);
    }
}
