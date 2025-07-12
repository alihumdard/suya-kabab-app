@component('mail::message')
# {{ $greeting }}

@if($userName)
    Hello **{{ $userName }}**,
@endif

{{ $message }}

@component('mail::panel')
## Your Verification Code

<div
    style="text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #667eea; padding: 20px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
    {{ $otp }}
</div>

⏰ This code expires in 15 minutes
@endcomponent

@component('mail::panel')
🔒 **Security Notice**

Never share this code with anyone. {{ $appName }} will never ask for your verification code via phone or email.
If you didn't request this code, please ignore this email or contact our support team.
@endcomponent

@if($type === 'email_verification')
    After verification, you'll be able to enjoy our delicious Suya and Kabab varieties!
@elseif($type === 'password_reset')
    If you didn't request a password reset, no further action is required.
@endif

@component('mail::button', ['url' => $appUrl])
Visit {{ $appName }}
@endcomponent

Thanks,<br>
{{ $appName }} Team

@component('mail::subcopy')
Need help? Contact our support team at support@suyakabab.com
@endcomponent
@endcomponent