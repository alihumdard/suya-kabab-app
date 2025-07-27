<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAddon;
use App\Models\AddonCategory;
use Illuminate\Http\Request;

class ProductAddonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProductAddon::with('category');

        // Handle search
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('slug', 'like', '%' . $searchTerm . '%')
                    ->orWhere('sku', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                        $categoryQuery->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        // Handle sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'alphabetical':
                    $query->orderBy('name', 'asc');
                    break;
                case 'price_low_high':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high_low':
                    $query->orderBy('price', 'desc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->orderBy('sort_order')->latest();
        }

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        $allowedPerPage = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $productAddons = $query->paginate($perPage)->appends($request->query());
        return view('pages.admin.addon_products.index', compact('productAddons'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $addonCategories = AddonCategory::active()->orderBy('name')->get();
        return view('pages.admin.addon_products.create', compact('addonCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:product_addons',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100|unique:product_addons',
            'track_quantity' => 'boolean',
            'quantity' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'addon_category_id' => 'required|exists:addon_categories,id',
        ]);

        $data = $request->all();
        $data['track_quantity'] = $request->has('track_quantity');

        ProductAddon::create($data);

        return redirect()->route('admin.product_addons.index')
            ->with('success', 'Product addon created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductAddon $productAddon)
    {
        $productAddon->load('category');
        return view('pages.admin.addon_products.show', compact('productAddon'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductAddon $productAddon)
    {
        $addonCategories = AddonCategory::active()->orderBy('name')->get();
        return view('pages.admin.addon_products.edit', compact('productAddon', 'addonCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductAddon $productAddon)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:product_addons,slug,' . $productAddon->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100|unique:product_addons,sku,' . $productAddon->id,
            'track_quantity' => 'boolean',
            'quantity' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'addon_category_id' => 'required|exists:addon_categories,id',
        ]);

        $data = $request->all();
        $data['track_quantity'] = $request->has('track_quantity');

        $productAddon->update($data);

        return redirect()->route('admin.product_addons.index')
            ->with('success', 'Product addon updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductAddon $productAddon)
    {
        $productAddon->delete();

        return redirect()->route('admin.product_addons.index')
            ->with('success', 'Product addon deleted successfully.');
    }
}
