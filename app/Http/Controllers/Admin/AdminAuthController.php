<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Admin;
use App\Models\OtpVerification;
use Carbon\Carbon;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        return view('pages.auth.login');
    }

    /**
     * Handle admin login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $admin = Auth::guard('admin')->user();

            // Check if admin is active
            if ($admin->status !== 'active') {
                Auth::guard('admin')->logout();
                return redirect()->back()
                    ->with('error', 'Your account has been deactivated.');
            }

            // Update last login
            $admin->update(['last_login_at' => Carbon::now()]);

            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->back()
            ->with('error', 'Invalid credentials')
            ->withInput($request->only('email'));
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Logged out successfully');
    }

    /**
     * Handle admin registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('name', 'email'));
        }

        try {
            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 'active',
                'role' => 'admin',
            ]);

            return redirect()->route('admin.login')
                ->with('success', 'Registration successful! Please login to continue.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput($request->only('name', 'email'));
        }
    }

    /**
     * Send password reset OTP to admin's email.
     */
    public function sendPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:admins,email'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return redirect()->back()
                ->with('error', 'Admin account not found with this email address.');
        }

        try {
            // Delete existing OTP records for this email
            OtpVerification::where('email', $request->email)->delete();

            // Send OTP using helper function
            sendOTP($request->email, 'password_reset', 'admin');

            return redirect()->route('admin.password.otp.show', ['email' => $request->email])
                ->with('success', 'Password reset code has been sent to your email address.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to send password reset code. Please try again.');
        }
    }

    /**
     * Reset admin password using OTP.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:admins,email'],
            'otp' => ['required', 'numeric'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        try {
            // Verify OTP
            $otpRecord = OtpVerification::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$otpRecord) {
                return redirect()->back()
                    ->with('error', 'Invalid or expired OTP. Please request a new password reset code.');
            }

            // Update admin password
            $admin = Admin::where('email', $request->email)->first();
            $admin->update([
                'password' => Hash::make($request->password),
                'updated_at' => Carbon::now(),
            ]);

            // Delete used OTP
            $otpRecord->delete();

            return redirect()->route('admin.login')
                ->with('success', 'Password has been reset successfully. Please login with your new password.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reset password. Please try again.');
        }
    }

    /**
     * Show password reset OTP verification form.
     */
    public function showPasswordOTP($email)
    {
        return view('pages.auth.password.otp', compact('email'));
    }

    /**
     * Verify password reset OTP.
     */
    public function verifyPasswordOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:admins,email'],
            'otp' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        try {
            // Verify OTP
            $otpRecord = OtpVerification::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$otpRecord) {
                return redirect()->back()
                    ->with('error', 'Invalid or expired OTP. Please request a new password reset code.');
            }

            // Redirect to password reset form with verified OTP
            return redirect()->route('admin.password.reset', [
                'email' => $request->email,
                'otp' => $request->otp
            ]);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to verify OTP. Please try again.');
        }
    }

    /**
     * Show password reset form.
     */
    public function showPasswordReset($email, $otp)
    {
        // Verify OTP is still valid
        $otpRecord = OtpVerification::where('email', $email)
            ->where('otp', $otp)
            ->where('type', 'password_reset')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return redirect()->route('admin.password.request')
                ->with('error', 'Invalid or expired reset link. Please request a new password reset.');
        }

        return view('pages.auth.password.reset', compact('email', 'otp'));
    }

    /**
     * Verify OTP for various purposes.
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        try {
            // Verify OTP
            $otpRecord = OtpVerification::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$otpRecord) {
                return redirect()->back()
                    ->with('error', 'Invalid or expired OTP. Please try again.');
            }

            // Handle different OTP types
            if ($otpRecord->type === 'registration') {
                // For registration verification
                $admin = Admin::where('email', $request->email)->first();
                if ($admin) {
                    $admin->update(['email_verified_at' => Carbon::now()]);
                }
            }

            // Delete used OTP
            $otpRecord->delete();

            return redirect()->route('admin.login')
                ->with('success', 'OTP verified successfully. Please login to continue.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to verify OTP. Please try again.');
        }
    }
}