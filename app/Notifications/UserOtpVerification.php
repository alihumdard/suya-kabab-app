<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserOtpVerification extends Notification
{


    public function __construct(protected string $email, protected ?string $name = null, protected string $otp, protected string $type) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Suyakabab Account Verification')
            ->view('emails.otp_verification', [
                'email' => $this->email,
                'type' => $this->type,
                'name' => $this->name,
                'otp' => $this->otp,
            ]);
    }
}
