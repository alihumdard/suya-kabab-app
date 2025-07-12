<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'customizations',
        'special_instructions',
        'addon_total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customizations' => 'array',
            'addon_total' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the cart item.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the total price for this cart item including add-ons.
     */
    public function getTotalPriceAttribute()
    {
        return ($this->product->price * $this->quantity) + $this->addon_total;
    }

    /**
     * Get the customizations in a formatted way.
     */
    public function getFormattedCustomizationsAttribute()
    {
        if (!$this->customizations || !is_array($this->customizations)) {
            return [];
        }

        return $this->customizations;
    }

    /**
     * Calculate addon total from customizations.
     */
    public function calculateAddonTotal()
    {
        if (!$this->customizations || !is_array($this->customizations)) {
            return 0;
        }

        $total = 0;
        foreach ($this->customizations as $customization) {
            if (isset($customization['price']) && isset($customization['quantity'])) {
                $total += $customization['price'] * $customization['quantity'];
            }
        }

        return $total;
    }

    /**
     * Scope a query to only include items for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}