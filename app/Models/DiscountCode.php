<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type', // fixed or percentage
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_amount' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Check if discount code is valid.
     */
    public function isValid($subtotal = 0)
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check if expired
        if ($this->expires_at && Carbon::now()->greaterThan($this->expires_at)) {
            return false;
        }

        // Check if not started yet
        if ($this->starts_at && Carbon::now()->lessThan($this->starts_at)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        // Check minimum amount
        if ($this->minimum_amount && $subtotal < $this->minimum_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount.
     */
    public function calculateDiscount($subtotal)
    {
        if (!$this->isValid($subtotal)) {
            return 0;
        }

        $discount = 0;

        if ($this->type === 'fixed') {
            $discount = $this->value;
        } elseif ($this->type === 'percentage') {
            $discount = ($subtotal * $this->value) / 100;
        }

        // Apply maximum discount limit
        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        // Don't let discount exceed subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return $discount;
    }

    /**
     * Mark discount code as used.
     */
    public function markAsUsed()
    {
        $this->increment('used_count');
        return $this;
    }

    /**
     * Scope for active discount codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid discount codes.
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', Carbon::now());
            });
    }
}