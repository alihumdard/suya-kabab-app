<?php

use App\Models\User;
use App\Models\Admin;
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
     * @param string $userType 'user' or 'admin'
     * @return void
     */
    function sendOTP($email, $type, $userType = 'user')
    {
        Log::info('Starting OTP send process', [
            'email' => $email,
            'type' => $type,
            'user_type' => $userType,
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);

        $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        Log::info('Generated OTP', [
            'email' => $email,
            'otp' => $otp,
            'type' => $type,
            'user_type' => $userType
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
            // Get user for personalized notification based on user type
            if ($userType === 'admin') {
                $user = Admin::where('email', $email)->first();
            } else {
                $user = User::where('email', $email)->first();
            }

            $userName = $user ? $user->name : null;

            Log::info('User found for notification', [
                'email' => $email,
                'user_type' => $userType,
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
                'user_type' => $userType,
                'user_name' => $userName
            ]);

            Notification::route('mail', $email)
                ->notify(new UserOtpVerification($email, $userName, $otp, $type));

            Log::info('Notification sent successfully', [
                'email' => $email,
                'otp' => $otp,
                'type' => $type,
                'user_type' => $userType
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

if (!function_exists('calculateDiscount')) {
    /**
     * Calculate discount amount and validate discount codes.
     *
     * @param string|null $discountCode
     * @param float $subtotal
     * @param \App\Models\User|null $user
     * @param bool $useRewardsBalance
     * @param float|null $rewardsAmount
     * @return array
     */
    function calculateDiscount($discountCode = null, $subtotal = 0, $user = null, $useRewardsBalance = false, $rewardsAmount = null)
    {
        $result = [
            'discount_amount' => 0,
            'discount_code' => $discountCode,
            'discount_details' => null,
            'rewards_discount' => 0,
            'total_savings' => 0,
            'is_valid' => false,
            'error_message' => null
        ];

        // Validate and calculate discount code
        if (!empty($discountCode)) {
            $discountCodeModel = \App\Models\DiscountCode::where('code', $discountCode)->first();

            if (!$discountCodeModel) {
                $result['error_message'] = 'Invalid discount code';
                return $result;
            }

            if (!$discountCodeModel->isValid($subtotal)) {
                $result['error_message'] = 'Discount code is not valid or expired';
                return $result;
            }

            $discountAmount = $discountCodeModel->calculateDiscount($subtotal);

            $result['discount_amount'] = $discountAmount;
            $result['discount_details'] = [
                'code' => $discountCodeModel->code,
                'type' => $discountCodeModel->type,
                'value' => $discountCodeModel->value,
                'minimum_amount' => $discountCodeModel->minimum_amount,
                'maximum_discount' => $discountCodeModel->maximum_discount,
                'calculated_discount' => $discountAmount
            ];
            $result['is_valid'] = true;
        }

        // Calculate total savings
        $result['total_savings'] = $result['discount_amount'];

        Log::info('Discount result', [
            'discount_amount' => $result['discount_amount'],
            'total_savings' => $result['total_savings']
        ]);

        return $result;
    }
}
