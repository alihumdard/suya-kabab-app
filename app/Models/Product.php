<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'compare_price',
        'cost_price',
        'sku',
        'barcode',
        'track_quantity',
        'quantity',
        'allow_backorder',
        'weight',
        'dimensions',
        'status',
        'featured',
        'meta_title',
        'meta_description',
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
            'compare_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'track_quantity' => 'boolean',
            'allow_backorder' => 'boolean',
            'featured' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the product images.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the product reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the product's approved reviews.
     */
    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    /**
     * Get the product's cart items.
     */
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the product's order items.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the available add-ons for this product.
     */
    public function addons()
    {
        return $this->belongsToMany(ProductAddon::class, 'product_addon_pivot')
            ->withPivot(['is_required', 'min_quantity', 'max_quantity', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Get the available add-ons grouped by category.
     */
    public function addonsGrouped()
    {
        return $this->addons()
            ->with('category')
            ->get()
            ->groupBy('category.name');
    }

    /**
     * Get active add-ons for this product.
     */
    public function activeAddons()
    {
        return $this->belongsToMany(ProductAddon::class, 'product_addon_pivot')
            ->where('product_addons.status', 'active')
            ->withPivot(['is_required', 'min_quantity', 'max_quantity', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope a query to search products.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', '%' . $term . '%')
            ->orWhere('description', 'like', '%' . $term . '%');
    }

    /**
     * Get the product's average rating.
     */
    public function getAverageRatingAttribute()
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    /**
     * Get the product's total reviews count.
     */
    public function getTotalReviewsAttribute()
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock()
    {
        if (!$this->track_quantity) {
            return true;
        }

        return $this->quantity > 0 || $this->allow_backorder;
    }

    /**
     * Get the product's main image.
     */
    public function getMainImageAttribute()
    {
        return $this->images()->first()?->image;
    }
}