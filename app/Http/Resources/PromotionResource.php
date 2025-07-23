<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            //'button_text' => $this->button_text,
            //'button_url' => $this->button_url,
            // 'sort_order' => $this->sort_order,
            'main_image_url' => $this->when(
                $this->relationLoaded('images') && $this->images->isNotEmpty(),
                function () {
                    return $this->images->first()->url;
                }
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,


            // Computed attributes
            'is_active' => $this->status === 'active',
            'has_button' => !empty($this->button_text) && !empty($this->button_url),
        ];
    }
}
