<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserOtpVerification extends Notification
{
    public $otp;
    public $type;
    public $userName;

    /**
     * Create a new notification instance.
     */
    public function __construct($otp, $type, $userName = null)
    {
        $this->otp = $otp;
        $this->type = $type;
        $this->userName = $userName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $message = $this->getMessage();

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.otp_verification_simple', [
                'otp' => $this->otp,
                'type' => $this->type,
                'userName' => $this->userName ?? $notifiable->name,
                'greeting' => $greeting,
                'message' => $message,
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'type' => $this->type,
            'user_name' => $this->userName ?? $notifiable->name,
        ];
    }

    /**
     * Get the subject based on OTP type.
     */
    private function getSubject(): string
    {
        return match ($this->type) {
            'email_verification' => 'Verify Your Email Address - ' . config('app.name'),
            'password_reset' => 'Reset Your Password - ' . config('app.name'),
            default => 'Your OTP Code - ' . config('app.name'),
        };
    }

    /**
     * Get the greeting based on OTP type.
     */
    private function getGreeting(): string
    {
        return match ($this->type) {
            'email_verification' => 'Welcome to ' . config('app.name') . '!',
            'password_reset' => 'Password Reset Request',
            default => 'Hello!',
        };
    }

    /**
     * Get the message based on OTP type.
     */
    private function getMessage(): string
    {
        return match ($this->type) {
            'email_verification' => 'Thank you for signing up! Please verify your email address using the OTP code below to complete your registration.',
            'password_reset' => 'You are receiving this email because we received a password reset request for your account. Use the OTP code below to reset your password.',
            default => 'Please use the OTP code below to proceed.',
        };
    }
}
