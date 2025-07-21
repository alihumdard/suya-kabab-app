<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreCategoryRequest;

class CategoryController extends Controller
{
    /**
     * Display the category management page.
     */
    public function index(Request $request)
    {
        $query = Category::with(['images'])->withCount('products');

        // Handle search
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('slug', 'like', '%' . $searchTerm . '%');
            });
        }

        // Handle sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'popular':
                    $query->orderBy('products_count', 'desc');
                    break;
                case 'latest':
                    $query->latest();
                    break;
                case 'alphabetical':
                    $query->orderBy('name', 'asc');
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $categories = $query->paginate(12)->appends(request()->query());

        return view('pages.admin.category', compact('categories'));
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request)
    {

        // Generate slug if not provided
        $slug = $request->slug ?: Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Create category
        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'status' => $request->status,

        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            try {
                // Use ImageHelper to save image to public/images/categories/
                $imagePath = ImageHelper::saveImage($image, 'images/categories');

                // Create polymorphic image record
                Image::create([
                    'imageable_type' => Category::class,
                    'imageable_id' => $category->id,
                    'image_path' => $imagePath,
                    'alt_text' => $category->name,
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

        return redirect()->route('admin.category')
            ->with('success', 'Category created successfully!');
    }
}