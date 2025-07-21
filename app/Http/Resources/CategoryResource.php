<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Image relationships
            'images' => ImageResource::collection($this->whenLoaded('images')),

            // Counts
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                fn() => $this->products->count()
            ),
            'active_products_count' => $this->when(
                isset($this->active_products_count),
                $this->active_products_count
            ),

            // Relationships (conditionally loaded to avoid circular references)
            'products' => $this->when(
                $this->relationLoaded('products') || $this->relationLoaded('activeProducts'),
                function () {
                    if ($this->relationLoaded('activeProducts')) {
                        return ProductResource::collection($this->activeProducts);
                    }
                    return ProductResource::collection($this->products);
                }
            ),

            // Computed attributes
            'is_active' => $this->status === 'active',
            'has_products' => $this->when(
                isset($this->active_products_count),
                $this->active_products_count > 0
            ),
        ];
    }
}
