<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Image;
use App\Models\OtpVerification;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Helpers\ImageHelper;
use App\Notifications\UserOtpVerification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(UserRegisterRequest $request)
    {
        $input = $request->validated();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'phone' => $input['phone'],
            'address' => $input['address'],
        ]);

        // Send OTP for email verification
        sendOTP($user->email, 'email_verification');

        return response()->json([
            'error' => false,
            'message' => 'Registration successful. Please verify your email with the OTP sent to your email address.',
            'data' => $user
        ], 201);
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()
            ], 403);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, (string) $user->password)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if email is verified
        if (is_null($user->email_verified_at) || empty($user->email_verified_at)) {
            return response()->json([
                'error' => true,
                'message' => 'Your email is not verified, Please verify your email first'
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'error' => true,
                'message' => 'Your account has been deactivated'
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => Carbon::now()]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'error' => false,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200);
    }

    /**
     * Verify user email with OTP.
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string', 'size:4']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()
            ], 403);
        }

        $otpRecord = OtpVerification::where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $user = User::where('email', $otpRecord->email)->first();
        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }

        // Mark email as verified
        $user->update(['email_verified_at' => Carbon::now()]);

        // Delete the OTP record for security
        $otpRecord->delete();

        return response()->json([
            'error' => false,
            'message' => 'Email verified successfully'
        ]);
    }

    /**
     * Send password reset OTP.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 403);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }

        // Send OTP for password reset
        sendOTP($user->email, 'password_reset');

        return response()->json([
            'error' => false,
            'message' => 'Password reset OTP sent to your email'
        ]);
    }

    /**
     * Reset password with OTP.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()
            ], 403);
        }


        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }

        // Update password
        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'error' => false,
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Resend email verification OTP.
     */
    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()
            ], 403);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }

        // Check rate limiting (max 5 OTPs per hour)
        $recentOTPs = OtpVerification::where('email', $request->email)
            ->where('created_at', '>', Carbon::now()->subHour())
            ->count();

        if ($recentOTPs >= 5) {
            return response()->json([
                'error' => true,
                'message' => 'Too many OTP requests. Please try again later.'
            ], 429);
        }

        sendOTP($user->email, 'email_verification');

        return response()->json([
            'error' => false,
            'message' => 'Email verification OTP sent successfully'
        ]);
    }

    /**
     * Get user profile.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $user->load('images');

        return response()->json([
            'error' => false,
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validatedData = $request->validated();

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            try {
                // Get old profile images before deleting
                $oldImages = $user->images()->get();

                // Delete old physical image files first
                foreach ($oldImages as $oldImage) {
                    $fullPath = public_path($oldImage->image_path);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                // Delete old profile image records from database
                $user->images()->delete();

                // Use ImageHelper to save image to public/images/profiles/
                $imagePath = ImageHelper::saveImage($request->file('profile_image'), 'images/profiles');

                // Create polymorphic image record
                Image::create([
                    'imageable_type' => User::class,
                    'imageable_id' => $user->id,
                    'image_path' => $imagePath,
                    'alt_text' => $user->name . ' profile picture',
                    'mime_type' => $request->file('profile_image')->getMimeType(),
                    'size' => $request->file('profile_image')->getSize(),
                    'is_active' => true,
                ]);

                // Remove profile_image from validated data since we handle it separately
                unset($validatedData['profile_image']);

            } catch (\Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to upload profile image. Please try again.'
                ], 500);
            }
        }

        $user->update($validatedData);

        return response()->json([
            'error' => false,
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user->fresh()->load('images'))
        ]);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'error' => false,
            'message' => 'Logged out successfully'
        ]);
    }


}