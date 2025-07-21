<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Image;
use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Http\Resources\PromotionResource;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    /**
     * Display a listing of the promotions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Promotion::with('images');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by current promotions (within date range)
            if ($request->has('current') && $request->current) {
                $query->current();
            }

            // Filter by active promotions
            if ($request->has('active') && $request->active) {
                $query->active();
            }

            // Search by title
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // Order by sort order
            $promotions = $query->ordered()->paginate($request->get('per_page', 10));

            return response()->json([
                'error' => false,
                'message' => 'Promotions retrieved successfully',
                'data' => $promotions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error retrieving promotions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created promotion in storage.
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        try {
            $promotion = Promotion::create($request->validated());

            // Handle image upload
            if ($request->hasFile('image')) {
                $this->handleImageUpload($request->file('image'), $promotion);
            }

            $promotion->load('images');

            return response()->json([
                'error' => false,
                'message' => 'Promotion created successfully',
                'data' => $promotion,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error creating promotion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified promotion.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $promotion = Promotion::with('images')->findOrFail($id);

            return response()->json([
                'error' => false,
                'message' => 'Promotion retrieved successfully',
                'data' => $promotion,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Promotion not found',
            ], 404);
        }
    }

    /**
     * Update the specified promotion in storage.
     */
    public function update(UpdatePromotionRequest $request, string $id): JsonResponse
    {
        try {
            $promotion = Promotion::findOrFail($id);

            $promotion->update($request->validated());

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old images
                $this->deletePromotionImages($promotion);
                // Upload new image
                $this->handleImageUpload($request->file('image'), $promotion);
            }

            $promotion->load('images');

            return response()->json([
                'error' => false,
                'message' => 'Promotion updated successfully',
                'data' => $promotion,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error updating promotion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified promotion from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $promotion = Promotion::findOrFail($id);

            // Delete associated images
            $this->deletePromotionImages($promotion);

            $promotion->delete();

            return response()->json([
                'error' => false,
                'message' => 'Promotion deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error deleting promotion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active promotions for public display.
     */
    public function active(): JsonResponse
    {
        try {
            $promotions = Promotion::with('images')
                ->active()
                ->current()
                ->ordered()
                ->get();

            return response()->json([
                'error' => false,
                'message' => 'Active promotions retrieved successfully',
                'data' => $promotions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error retrieving active promotions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle image upload for promotion.
     */
    private function handleImageUpload($file, Promotion $promotion): void
    {
        try {
            // Use ImageHelper to save image to public/images/promotions/
            $imagePath = ImageHelper::saveImage($file, 'images/promotions');

            Image::create([
                'imageable_type' => Promotion::class,
                'imageable_id' => $promotion->id,
                'image_path' => $imagePath,
                'alt_text' => $promotion->title,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'dimensions' => json_encode([
                    'width' => null, // You can add image dimensions detection here if needed
                    'height' => null
                ]),
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to upload promotion image: ' . $e->getMessage());
        }
    }

    /**
     * Delete all images associated with a promotion.
     */
    private function deletePromotionImages(Promotion $promotion): void
    {
        $images = $promotion->images;

        foreach ($images as $image) {
            // Delete file from public directory
            $fullPath = public_path($image->image_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete image record
            $image->delete();
        }
    }
}
