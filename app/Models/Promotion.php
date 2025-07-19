<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Promotion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'button_text',
        'button_url',
        'sort_order',
    ];

    /**
     * Get the promotion images.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable')->active()->orderBy('created_at');
    }

    /**
     * Get the main image for the promotion.
     */
    public function getMainImageAttribute()
    {
        return $this->images()->first();
    }

    /**
     * Get the main image URL for the promotion.
     */
    public function getMainImageUrlAttribute()
    {
        return $this->main_image?->url;
    }

    /**
     * Scope a query to only include active promotions.
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
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }
}
