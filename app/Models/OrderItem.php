<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'customizations',
        'special_instructions',
        'addon_total',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'customizations' => 'array',
            'addon_total' => 'decimal:2',
        ];
    }

    /**
     * Get the order that owns the item.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the total price for this order item including add-ons.
     */
    public function getTotalWithAddonsAttribute()
    {
        return $this->total + $this->addon_total;
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
}