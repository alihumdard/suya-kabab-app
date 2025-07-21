<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Image;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;

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

        return view('pages.admin.product', compact('products', 'categories'));
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

        return redirect()->route('admin.product')
            ->with('success', 'Product created successfully!');
    }
}