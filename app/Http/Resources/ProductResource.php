<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price' => $this->price,
            'track_quantity' => $this->track_quantity,
            'quantity' => $this->quantity,
            'main_image_url' => $this->when(
                $this->relationLoaded('images') && $this->images->isNotEmpty(),
                function () {
                    return $this->images->first()->url;
                }
            ),
            'allow_backorder' => $this->allow_backorder,
            'weight' => $this->weight,
            'status' => $this->status,
            'featured' => $this->featured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'category' => $this->whenLoaded('category'),
            'addons' => $this->when(
                $this->relationLoaded('addons'),
                function () {
                    return $this->addons->map(function ($addon) {
                        return [
                            'id' => $addon->id,
                            'name' => $addon->name,
                            'slug' => $addon->slug,
                            'description' => $addon->description,
                            'price' => $addon->price,
                            'image' => $addon->image,
                            'sku' => $addon->sku,
                            'status' => $addon->status,
                            'in_stock' => $addon->isInStock(),
                            'available_quantity' => $addon->track_quantity ? $addon->quantity : null,

                            // Pivot data from product_addon_pivot table
                            'is_required' => $addon->pivot->is_required,
                            'min_quantity' => $addon->pivot->min_quantity,
                            'max_quantity' => $addon->pivot->max_quantity,
                            'sort_order' => $addon->pivot->sort_order,
                        ];
                    })->sortBy('sort_order')->values();
                }
            ),

            // Computed attributes
            'average_rating' => $this->average_rating,
            'total_reviews' => $this->total_reviews,
            'in_stock' => $this->isInStock(),

            // User-specific data (only when user is authenticated)
            'is_favorite' => $this->when(
                auth()->check(),
                function () {
                    return $this->relationLoaded('favoritedBy')
                        ? $this->favoritedBy->contains('id', auth()->id())
                        : false;
                }
            ),
        ];
    }


}