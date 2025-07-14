<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Send OTP for email verification
        sendOTP($user->email, 'email_verification');

        return response()->json([
            'success' => true,
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
            ], 422);
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
        ]);
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
            ], 422);
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
            ], 422);
        }

        $user = User::where('email', $request->email)->where('email_verified_at', '!=', null)->first();

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
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
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
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->where('email_verified_at', null)->first();
        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User not found'
            ], 404);
        }

        // Check if email is already verified
        if (!is_null($user->email_verified_at) && !empty($user->email_verified_at)) {
            return response()->json([
                'error' => true,
                'message' => 'Email is already verified'
            ], 400);
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
        return response()->json([
            'error' => false,
            'data' => $request->user()
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
            // Delete old profile image if it exists
            $oldProfileImage = $user->getRawOriginal('profile_image');
            if ($oldProfileImage && Storage::disk('public')->exists($oldProfileImage)) {
                Storage::disk('public')->delete($oldProfileImage);
            }

            // Store new profile image
            $profileImagePath = $request->file('profile_image')->store('images', 'public');
            $validatedData['profile_image'] = $profileImagePath;
        }

        $user->update($validatedData);

        return response()->json([
            'error' => false,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()
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