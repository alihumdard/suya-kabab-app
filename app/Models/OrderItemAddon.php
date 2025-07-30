<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemAddon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_item_id',
        'product_addon_id',
        'quantity',
        'price',
    ];

    /**
     * Get the order item that owns the addon.
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the product addon associated with the order item addon.
     */
    public function productAddon()
    {
        return $this->belongsTo(ProductAddon::class);
    }
}
