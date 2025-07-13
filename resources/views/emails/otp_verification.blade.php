<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        /* Base */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #2d3748;
            height: 100vh;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin: 20px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            width: 180px;
            height: auto;
            margin-bottom: 20px;
            display: inline-block;
        }
        .title {
            color: #000000;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }
        .message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 25px;
        }
        .otp-container {
            background-color: #F0F9FF;
            border: 2px solid #0EA5E9;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #0EA5E9;
            letter-spacing: 4px;
            margin: 10px 0;
        }
        .important-notice {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #718096;
            font-size: 14px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .button {
            display: block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px auto;
            text-align: center;
            width: fit-content;
        }
        .button:hover {
            background-color: #4338CA;
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <img src="{{ asset('assets/images/Logo.png') }}" alt="Suya Kabab" class="logo">
                <h1 class="title">Email Verification</h1>
            </div>

            <div class="message">
                @if($name && !is_null($name))
                    Dear <strong>{{$name}}</strong>,<br><br>
                @else
                    Hello,<br><br>
                @endif
                
                @if($type == 'email_verification')
                    Thank you for signing up with Suya Kabab! Please use the verification code below to verify your email address and complete your registration.
                @elseif($type == 'password_reset')
                    You have requested to reset your password. Please use the verification code below to proceed with your password reset.
                @elseif($type == 'login_verification')
                    A login attempt was made to your account. Please use the verification code below to complete your login.
                @else
                    Please use the verification code below to complete your request.
                @endif
            </div>

            <div class="otp-container">
                <p style="margin: 0; font-size: 16px; color: #4a5568;">Your verification code is:</p>
                <div class="otp-code">{{ $otp }}</div>
                <p style="margin: 0; font-size: 14px; color: #718096;">This code will expire in 15 minutes</p>
            </div>

            <div class="important-notice">
                <strong>Security Notice:</strong><br>
                • This code is valid for 15 minutes only<br>
                • Do not share this code with anyone<br>
                • If you didn't request this code, please ignore this email
            </div>

            <div class="message">
                <p>If you have any questions or need assistance, please contact us at <a href="mailto:support@suyakabab.com">support@suyakabab.com</a>.</p>
            </div>

            <div class="footer">
                <p>Best regards,<br>Team Suya Kabab</p>
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html> 