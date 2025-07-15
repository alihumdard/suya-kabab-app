<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
        $query = Category::withCount('products');

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

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Generate unique filename with original extension
            $imageName = 'category_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            try {
                $imagePath = $image->storeAs('categories', $imageName, 'public');
            } catch (\Exception $e) {
                // Handle upload error gracefully
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to upload image. Please try again.');
            }
        }

        // Create category
        Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'image' => $imagePath,
            'status' => $request->status,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.category')
            ->with('success', 'Category created successfully!');
    }
}