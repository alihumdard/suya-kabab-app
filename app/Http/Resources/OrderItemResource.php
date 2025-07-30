<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'customizations' => $this->customizations,
            'special_instructions' => $this->special_instructions,
            'addon_total' => $this->addon_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product' => [
                'id' => $this->product->id,
                'category_id' => $this->product->category_id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'description' => $this->product->description,
                'short_description' => $this->product->short_description,
                'price' => $this->product->price,
                'track_quantity' => $this->product->track_quantity,
                'quantity' => $this->product->quantity,
                'allow_backorder' => $this->product->allow_backorder,
                'weight' => $this->product->weight,
                'status' => $this->product->status,
                'featured' => $this->product->featured,
                'created_at' => $this->product->created_at,
                'updated_at' => $this->product->updated_at,
                'main_image_url' => $this->when(
                    $this->product->relationLoaded('images') && $this->product->images->isNotEmpty(),
                    function () {
                        return $this->product->images->first()->url;
                    }
                ),
            ],
            'addons' => $this->whenLoaded('addons', function() {
                // Get the original request data to see which addons were actually requested
                $request = request();
                $requestedAddons = [];
                
                // Find this item in the request
                foreach (($request->input('items') ?? []) as $requestItem) {
                    if (($requestItem['product_id'] ?? null) == $this->product_id) {
                        $baseFields = ['product_id', 'quantity', 'customizations', 'special_instructions', 'addon_total'];
                        $requestedAddonTypes = array_diff(array_keys($requestItem), $baseFields);
                        
                        // Get all requested addon IDs by category
                        foreach ($requestedAddonTypes as $type) {
                            if (is_array($requestItem[$type] ?? null)) {
                                $requestedAddons[$type] = array_map(function($item) {
                                    return $item['id'] ?? null;
                                }, $requestItem[$type]);
                            }
                        }
                        break;
                    }
                }
                
                // Group addons by their category name (lowercase for consistency)
                $groupedAddons = [];
                
                foreach ($this->addons as $addon) {
                    $categoryName = strtolower($addon->productAddon->category->name ?? 'extras');
                    
                    // Skip if this addon wasn't in the request
                    if (!isset($requestedAddons[$categoryName]) || 
                        !in_array($addon->productAddon->id, $requestedAddons[$categoryName])) {
                        continue;
                    }
                    
                    if (!isset($groupedAddons[$categoryName])) {
                        $groupedAddons[$categoryName] = [];
                    }
                    
                    // Format addon data to match the required structure
                    $addonData = [
                        'id' => $addon->productAddon->id,
                        'name' => $addon->productAddon->name,
                        'slug' => $addon->productAddon->slug,
                        'description' => $addon->productAddon->description,
                        'price' => number_format($addon->price, 2, '.', ''), // Use the price from the order (in case it changes later)
                        'image' => $addon->productAddon->image,
                        'status' => $addon->productAddon->status,
                        'in_stock' => true, // Since it was ordered, we assume it was in stock
                        'available_quantity' => null, // Not relevant for ordered items
                        'is_required' => $addon->productAddon->is_required ?? 0,
                        'min_quantity' => $addon->productAddon->min_quantity ?? 0,
                        'max_quantity' => $addon->productAddon->max_quantity ?? 3,
                        'sort_order' => $addon->productAddon->sort_order ?? 0,
                        'quantity' => $addon->quantity, // Include the quantity ordered
                        'total' => number_format($addon->price * $addon->quantity, 2, '.', '') // Calculate total for this addon
                    ];
                    
                    // Add to the appropriate category
                    $groupedAddons[$categoryName][] = $addonData;
                }
                
                // Sort each category's addons by sort_order
                foreach ($groupedAddons as &$categoryAddons) {
                    usort($categoryAddons, function($a, $b) {
                        return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
                    });
                }
                
                return $groupedAddons;
            }, new \stdClass()),
        ];
    }
}
