<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'image_path',
        'alt_text',
        'mime_type',
        'size',
        'dimensions',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dimensions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent imageable model.
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        // For public assets, use asset() helper
        return asset($this->image_path);
    }

    /**
     * Scope active images.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by imageable type.
     */
    public function scopeForModel($query, $modelClass)
    {
        return $query->where('imageable_type', $modelClass);
    }
}