<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonCategory;
use Illuminate\Http\Request;

class AddonCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AddonCategory::query();

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
                case 'alphabetical':
                    $query->orderBy('name', 'asc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->orderBy('sort_order')->latest();
        }

        $addonCategories = $query->get();
        return view('pages.admin.addon_categories.index', compact('addonCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.addon_categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:addon_categories',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        AddonCategory::create($request->all());

        return redirect()->route('admin.addon_categories.index')
            ->with('success', 'Addon category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AddonCategory $addonCategory)
    {
        return view('pages.admin.addon_categories.show', compact('addonCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AddonCategory $addonCategory)
    {
        return view('pages.admin.addon_categories.edit', compact('addonCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AddonCategory $addonCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:addon_categories,slug,' . $addonCategory->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $addonCategory->update($request->all());

        return redirect()->route('admin.addon_categories.index')
            ->with('success', 'Addon category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AddonCategory $addonCategory)
    {
        $addonCategory->delete();

        return redirect()->route('admin.addon_categories.index')
            ->with('success', 'Addon category deleted successfully.');
    }
}