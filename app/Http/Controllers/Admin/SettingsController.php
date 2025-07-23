<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Display app settings.
     */
    public function index()
    {
        $settings = Setting::orderBy('key')->get()->groupBy(function ($setting) {
            // Group settings by type for better organization
            if (in_array($setting->key, ['delivery_charges', 'free_delivery_minimum', 'min_order_amount'])) {
                return 'Order & Delivery';
            } elseif (in_array($setting->key, ['app_name', 'app_phone', 'app_email'])) {
                return 'Contact Information';
            } elseif (in_array($setting->key, ['delivery_time_estimate', 'pickup_time_estimate'])) {
                return 'Time Estimates';
            } else {
                return 'General';
            }
        });

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings
            ]
        ]);
    }

    /**
     * Update app settings.
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.type' => 'nullable|string|in:string,integer,decimal,boolean,json',
            'settings.*.description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 403);
        }

        try {
            foreach ($request->settings as $settingData) {
                Setting::set(
                    $settingData['key'],
                    $settingData['value'],
                    $settingData['type'] ?? 'string',
                    $settingData['description'] ?? null
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings'
            ], 500);
        }
    }

    /**
     * Update a single setting.
     */
    public function updateSingle(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'nullable|string|in:string,integer,decimal,boolean,json',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 403);
        }

        try {
            $setting = Setting::set(
                $key,
                $request->value,
                $request->type ?? 'string',
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'setting' => $setting
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting'
            ], 500);
        }
    }

    /**
     * Get delivery settings for quick access.
     */
    public function getDeliverySettings()
    {
        $deliverySettings = [
            'delivery_charges' => Setting::getDeliveryCharges(),
            'free_delivery_minimum' => Setting::getFreeDeliveryMinimum(),
            'delivery_time_estimate' => Setting::get('delivery_time_estimate', '30-45'),
            'pickup_time_estimate' => Setting::get('pickup_time_estimate', '15-20'),
            'max_delivery_distance' => Setting::get('max_delivery_distance', 10),
        ];

        return response()->json([
            'success' => true,
            'data' => $deliverySettings
        ]);
    }

    /**
     * Update delivery settings.
     */
    public function updateDeliverySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'delivery_charges' => 'required|numeric|min:0',
            'free_delivery_minimum' => 'required|numeric|min:0',
            'delivery_time_estimate' => 'nullable|string',
            'pickup_time_estimate' => 'nullable|string',
            'max_delivery_distance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 403);
        }

        try {
            Setting::set('delivery_charges', $request->delivery_charges, 'decimal');
            Setting::set('free_delivery_minimum', $request->free_delivery_minimum, 'decimal');

            if ($request->has('delivery_time_estimate')) {
                Setting::set('delivery_time_estimate', $request->delivery_time_estimate, 'string');
            }

            if ($request->has('pickup_time_estimate')) {
                Setting::set('pickup_time_estimate', $request->pickup_time_estimate, 'string');
            }

            if ($request->has('max_delivery_distance')) {
                Setting::set('max_delivery_distance', $request->max_delivery_distance, 'decimal');
            }

            return response()->json([
                'success' => true,
                'message' => 'Delivery settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery settings'
            ], 500);
        }
    }
}