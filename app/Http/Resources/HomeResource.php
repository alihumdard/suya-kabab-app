<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->when(
                isset($this->resource['user']) && $this->resource['user'],
                new UserResource($this->resource['user'])
            ),
            'products' => ProductResource::collection($this->resource['products'] ?? []),
            'categories' => CategoryResource::collection($this->resource['categories'] ?? []),
            'promotions' => PromotionResource::collection($this->resource['promotions'] ?? []),
        ];
    }
}
