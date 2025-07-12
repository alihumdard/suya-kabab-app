<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $greeting ?? 'OTP Verification' }} - {{ $appName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #555555;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .otp-container {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
        }

        .otp-label {
            color: white;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .otp-code {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            padding: 20px 30px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            display: inline-block;
            margin: 10px 0;
        }

        .otp-expires {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 15px;
        }

        .security-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }

        .security-notice h3 {
            color: #856404;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .security-notice p {
            color: #856404;
            font-size: 14px;
            margin: 0;
        }

        .security-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .social-links {
            margin-top: 20px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 8px;
            background-color: #667eea;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            text-align: center;
            line-height: 24px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #667eea 50%, transparent 100%);
            margin: 30px 0;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 0;
                box-shadow: none;
            }

            .header,
            .content,
            .footer {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .greeting {
                font-size: 20px;
            }

            .otp-code {
                font-size: 28px;
                letter-spacing: 6px;
                padding: 15px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🍢 {{ $appName }}</h1>
            <p>Your favorite Suya & Kabab delivery service</p>
        </div>

        <!-- Content -->
        <div class="content">
            <h2 class="greeting">{{ $greeting }}</h2>

            @if($userName)
                <p style="font-size: 16px; color: #666; margin-bottom: 20px;">
                    Hello <strong>{{ $userName }}</strong>,
                </p>
            @endif

            <p class="message">{{ $message }}</p>

            <!-- OTP Container -->
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expires">⏰ This code expires in 15 minutes</div>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <h3>
                    <svg class="security-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"></path>
                    </svg>
                    Security Notice
                </h3>
                <p>
                    Never share this code with anyone. {{ $appName }} will never ask for your verification code via
                    phone or email.
                    If you didn't request this code, please ignore this email or contact our support team.
                </p>
            </div>

            <div class="divider"></div>

            @if($type === 'email_verification')
                <p style="text-align: center; color: #666; font-size: 14px;">
                    After verification, you'll be able to enjoy our delicious Suya and Kabab varieties!
                </p>
            @elseif($type === 'password_reset')
                <p style="text-align: center; color: #666; font-size: 14px;">
                    If you didn't request a password reset, no further action is required.
                </p>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>
                Need help? <a href="mailto:support@suyakabab.com">Contact Support</a> |
                <a href="{{ $appUrl }}">Visit Website</a>
            </p>

            <div class="social-links">
                <a href="#" title="Facebook">📘</a>
                <a href="#" title="Instagram">📷</a>
                <a href="#" title="Twitter">🐦</a>
                <a href="#" title="WhatsApp">💬</a>
            </div>

            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                This is an automated message, please do not reply to this email.
            </p>
        </div>
    </div>
</body>

</html>