<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Image;
use App\Models\ProductAddon;
use App\Models\AddonCategory;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display the product management page.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images']);

        // Handle search
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('short_description', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                        $categoryQuery->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        // Handle sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'popular':
                    $query->withCount('orderItems')->orderBy('order_items_count', 'desc');
                    break;
                case 'latest':
                    $query->latest();
                    break;
                case 'price_low_high':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high_low':
                    $query->orderBy('price', 'desc');
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $products = $query->paginate(12)->appends(request()->query());
        $categories = Category::active()->orderBy('name')->get();
        $addonCategories = AddonCategory::active()->with('addons')->orderBy('name')->get();

        return view('pages.admin.products.index', compact('products', 'categories', 'addonCategories'));
    }

    /**
     * Store a new product.
     */
    public function store(StoreProductRequest $request)
    {

        // Generate slug if not provided
        $slug = $request->slug ?: Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Create product
        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'status' => $request->status,
            'quantity' => $request->quantity ?? 0,
            'track_quantity' => $request->has('track_quantity'),
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            try {
                // Use ImageHelper to save image to public/images/products/
                $imagePath = ImageHelper::saveImage($image, 'images/products');

                // Create polymorphic image record
                Image::create([
                    'imageable_type' => Product::class,
                    'imageable_id' => $product->id,
                    'image_path' => $imagePath,
                    'alt_text' => $product->name,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'dimensions' => json_encode([
                        'width' => null, // You can add image dimensions detection here if needed
                        'height' => null
                    ]),
                    'is_active' => true,
                ]);
            } catch (\Exception $e) {
                // Handle upload error gracefully
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to upload image. Please try again.');
            }
        }

        // Handle addon relationships
        if ($request->has('addons') && is_array($request->addons)) {
            $pivotData = [];
            foreach ($request->addons as $addonId => $addonConfig) {
                if (isset($addonConfig['selected']) && $addonConfig['selected']) {
                    $pivotData[] = [
                        'product_id' => $product->id,
                        'product_addon_id' => $addonId,
                        'min_quantity' => isset($addonConfig['min_quantity']) ? (int) $addonConfig['min_quantity'] : 0,
                        'max_quantity' => isset($addonConfig['max_quantity']) ? (int) $addonConfig['max_quantity'] : 3,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($pivotData)) {
                DB::table('product_addon_pivot')->insert($pivotData);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully!');
    }

    /**
     * Show the edit form for a product.
     */
    public function edit(Product $product)
    {
        $categories = Category::active()->orderBy('name')->get();
        $addonCategories = AddonCategory::active()->with('addons')->orderBy('name')->get();
        $product->load('addons'); // Load existing addons

        return view('pages.admin.products.edit', compact('product', 'categories', 'addonCategories'));
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        // Generate slug if not provided
        $slug = $request->slug ?: Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        // Check for unique slug (excluding current product)
        while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Update product
        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'status' => $request->status,
            'quantity' => $request->quantity ?? 0,
            'track_quantity' => $request->has('track_quantity'),
            'weight' => $request->weight,
            'featured' => $request->has('featured'),
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            try {
                // Delete old image if exists
                $oldImage = $product->images()->first();
                if ($oldImage) {
                    // Delete physical file if it exists
                    $oldImagePath = public_path($oldImage->image_path);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                    $oldImage->delete();
                }

                // Use ImageHelper to save new image to public/images/products/
                $imagePath = ImageHelper::saveImage($image, 'images/products');

                // Create new polymorphic image record
                Image::create([
                    'imageable_type' => Product::class,
                    'imageable_id' => $product->id,
                    'image_path' => $imagePath,
                    'alt_text' => $product->name,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'dimensions' => json_encode([
                        'width' => null, // You can add image dimensions detection here if needed
                        'height' => null
                    ]),
                    'is_active' => true,
                ]);
            } catch (\Exception $e) {
                // Handle upload error gracefully
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to upload image. Please try again.');
            }
        }

        // Handle addon relationships
        if ($request->has('addons') && is_array($request->addons)) {
            // First, remove all existing addon relationships
            $product->addons()->detach();

            // Then add the new ones
            $pivotData = [];
            foreach ($request->addons as $addonId => $addonConfig) {
                if (isset($addonConfig['selected']) && $addonConfig['selected']) {
                    $pivotData[] = [
                        'product_id' => $product->id,
                        'product_addon_id' => $addonId,
                        'min_quantity' => isset($addonConfig['min_quantity']) ? (int) $addonConfig['min_quantity'] : 0,
                        'max_quantity' => isset($addonConfig['max_quantity']) ? (int) $addonConfig['max_quantity'] : 3,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($pivotData)) {
                DB::table('product_addon_pivot')->insert($pivotData);
            }
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        try {
            // Delete associated images
            $images = $product->images;
            foreach ($images as $image) {
                // Delete physical file if it exists
                $imagePath = public_path($image->image_path);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $image->delete();
            }

            // Delete the product
            $productName = $product->name;
            $product->delete();

            return redirect()->route('admin.products.index')
                ->with('success', "Product '{$productName}' deleted successfully!");

        } catch (\Exception $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Failed to delete product. Please try again.');
        }
    }
}