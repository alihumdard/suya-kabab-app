<?php

use App\Models\User;
use App\Models\OtpVerification;
use App\Notifications\UserOtpVerification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

if (!function_exists('sendOTP')) {
    /**
     * Send OTP via email notification.
     *
     * @param string $email
     * @param string $type
     * @return void
     */
    function sendOTP($email, $type)
    {
        Log::info('Starting OTP send process', [
            'email' => $email,
            'type' => $type,
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);

        $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        Log::info('Generated OTP', [
            'email' => $email,
            'otp' => $otp,
            'type' => $type
        ]);

        // Save OTP to database
        try {
            $otpRecord = OtpVerification::create([
                'email' => $email,
                'otp' => $otp,
                'type' => $type,
                'expires_at' => Carbon::now()->addMinutes(15),
            ]);

            Log::info('OTP saved to database', [
                'email' => $email,
                'otp_id' => $otpRecord->id,
                'expires_at' => $otpRecord->expires_at->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save OTP to database', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return;
        }

        try {
            // Get user for personalized notification
            $user = User::where('email', $email)->first();
            $userName = $user ? $user->name : null;

            Log::info('User found for notification', [
                'email' => $email,
                'user_id' => $user ? $user->id : null,
                'user_name' => $userName
            ]);

            // Log mail configuration
            Log::info('Mail configuration', [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name')
            ]);

            // Send notification
            Log::info('Attempting to send notification', [
                'email' => $email,
                'otp' => $otp,
                'type' => $type,
                'user_name' => $userName
            ]);

            Notification::route('mail', $email)
                ->notify(new UserOtpVerification($otp, $type, $userName));

            Log::info('Notification sent successfully', [
                'email' => $email,
                'otp' => $otp,
                'type' => $type
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send OTP notification', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}

