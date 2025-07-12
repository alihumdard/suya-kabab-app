<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CrmController extends Controller
{
    public function edit()
    {
        $content = HomepageContent::firstOrCreate([], [
            'main_heading' => 'Default Main Heading',
            'main_content' => 'This is the default main content paragraph.',
            'plans_main_heading' => 'Choose Your Plan',
            'plans' => [],
            'why_choose_us_main_heading' => 'Why Choose Us?',
            'why_choose_us_items' => [],
        ]);

        return view('crm', compact('content'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'main_heading' => 'nullable|string',
            'main_content' => 'nullable|string',
            'plans_main_heading' => 'nullable|string',
            'plans' => 'nullable|array',
            'plans.*.image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'plans.*.heading' => 'required_with:plans|string',
            'plans.*.description' => 'nullable|string',
            'plans.*.details' => 'nullable|array',
            'plans.*.price' => 'nullable|string',
            'plans.*.stripe_price_id' => 'nullable|string', // Add this line
            'why_choose_us_main_heading' => 'nullable|string',
            'why_choose_us_items' => 'nullable|array',
        ]);

        $content = HomepageContent::first();
        $currentPlans = $content->plans ?? [];
        $newPlansData = [];

        if ($request->has('plans')) {
            foreach ($request->plans as $index => $planData) {
                $newPlan = $currentPlans[$index] ?? [];

                if (isset($planData['image'])) {
                    if (isset($newPlan['image_path'])) {
                        Storage::disk('public')->delete($newPlan['image_path']);
                    }
                    $newPlan['image_path'] = $planData['image']->store('plan_images', 'public');
                }

                $newPlan['heading'] = $planData['heading'];
                $newPlan['description'] = $planData['description'];
                $newPlan['details'] = isset($planData['details']) ? array_filter($planData['details']) : [];
                $newPlan['price'] = $planData['price'];
                $newPlan['stripe_price_id'] = $planData['stripe_price_id'] ?? null; // 2. ADD THIS LINE TO SAVE THE ID
                $newPlansData[] = $newPlan;
            }
        }

        $content->update([
            'main_heading' => $request->main_heading,
            'main_content' => $request->main_content,
            'plans_main_heading' => $request->plans_main_heading,
            'plans' => $newPlansData,
            'why_choose_us_main_heading' => $request->why_choose_us_main_heading,
            'why_choose_us_items' => isset($request->why_choose_us_items) ? array_filter($request->why_choose_us_items) : [],
        ]);

        return redirect()->route('admin.crm.edit')->with('success', 'Homepage content updated successfully!');
    }
}