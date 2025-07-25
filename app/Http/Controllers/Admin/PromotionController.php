<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Image;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    /**
     * Display the promotion management page.
     */
    public function index(Request $request)
    {
        $query = Promotion::with(['images']);

        // Handle search
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Handle status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Handle sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'latest':
                    $query->latest();
                    break;
                case 'oldest':
                    $query->oldest();
                    break;
                case 'alphabetical':
                    $query->orderBy('title', 'asc');
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        $promotions = $query->paginate(12)->appends(request()->query());

        return view('pages.admin.promotions.index', compact('promotions'));
    }

    /**
     * Store a new promotion.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive,expired'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create promotion with default values
        $promotion = Promotion::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'sort_order' => 0,
            'button_text' => null,
            'button_url' => null,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            try {
                // Use ImageHelper to save image to public/images/promotions/
                $imagePath = ImageHelper::saveImage($image, 'images/promotions');

                // Create polymorphic image record
                Image::create([
                    'imageable_type' => Promotion::class,
                    'imageable_id' => $promotion->id,
                    'image_path' => $imagePath,
                    'alt_text' => $promotion->title,
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

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion created successfully!');
    }

    /**
     * Show the edit form for a promotion.
     */
    public function edit(Promotion $promotion)
    {
        $promotion->load('images');
        return view('pages.admin.promotions.edit', compact('promotion'));
    }

    /**
     * Update the specified promotion.
     */
    public function update(Request $request, Promotion $promotion)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:active,inactive,expired'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update promotion
        $promotion->update([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            try {
                // Delete old image if exists
                $oldImage = $promotion->images()->first();
                if ($oldImage) {
                    // Delete physical file if it exists
                    $oldImagePath = public_path($oldImage->image_path);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                    $oldImage->delete();
                }

                // Use ImageHelper to save new image to public/images/promotions/
                $imagePath = ImageHelper::saveImage($image, 'images/promotions');

                // Create new polymorphic image record
                Image::create([
                    'imageable_type' => Promotion::class,
                    'imageable_id' => $promotion->id,
                    'image_path' => $imagePath,
                    'alt_text' => $promotion->title,
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

        return redirect()->route('admin.promotions.index')
            ->with('success', 'Promotion updated successfully!');
    }

    /**
     * Remove the specified promotion from storage.
     */
    public function destroy(Promotion $promotion)
    {
        try {
            // Delete associated images
            $images = $promotion->images;
            foreach ($images as $image) {
                // Delete physical file if it exists
                $imagePath = public_path($image->image_path);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $image->delete();
            }

            // Delete the promotion
            $promotionTitle = $promotion->title;
            $promotion->delete();

            return redirect()->route('admin.promotions.index')
                ->with('success', "Promotion '{$promotionTitle}' deleted successfully!");

        } catch (\Exception $e) {
            return redirect()->route('admin.promotions.index')
                ->with('error', 'Failed to delete promotion. Please try again.');
        }
    }

    /**
     * Toggle the status of a promotion between active and inactive.
     */
    public function toggleStatus(Promotion $promotion)
    {
        try {
            $newStatus = $promotion->status === 'active' ? 'inactive' : 'active';

            $promotion->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'activated' : 'deactivated';

            return redirect()->route('admin.promotions.index')
                ->with('success', "Promotion '{$promotion->title}' has been {$statusText}!");

        } catch (\Exception $e) {
            return redirect()->route('admin.promotions.index')
                ->with('error', 'Failed to update promotion status. Please try again.');
        }
    }
}