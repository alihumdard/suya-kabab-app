<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'image',
        'sku',
        'track_quantity',
        'quantity',
        'sort_order',
        'status',
        'addon_category_id',
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
            'track_quantity' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the addon.
     */
    public function category()
    {
        return $this->belongsTo(AddonCategory::class, 'addon_category_id');
    }

    /**
     * Get the products that use this add-on.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_addon_pivot')
            ->withPivot(['is_required', 'min_quantity', 'max_quantity', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active add-ons.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if add-on is in stock.
     */
    public function isInStock()
    {
        if (!$this->track_quantity) {
            return true;
        }

        return $this->quantity > 0;
    }
}