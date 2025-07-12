<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify OTP</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body class="min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-200">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Enter OTP</h2>
        <p class="text-center text-sm text-gray-600 mb-6">A 6-digit code has been sent to {{ $email }}.</p>
        
        <div id="alert-message" class="hidden my-2 text-sm text-center p-2 rounded"></div>
        
        <form method="POST" action="{{ Str::contains(url()->current(), 'register') ? route('register.otp.verify') : route('password.otp.verify') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <div class="mb-5">
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <input type="text" id="otp" name="otp" class="w-full px-4 py-3 rounded-lg border @error('otp') border-red-500 @else border-gray-300 @enderror focus:outline-none focus:ring-2 focus:ring-orange-500" required>
                @error('otp')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full text-white font-semibold py-3 rounded-lg transition duration-200" style="background-color: #3cb371;">Verify Code</button>
        </form>

        @if(Str::contains(url()->current(), 'register'))
        <div class="text-center mt-4">
            <button id="resend-otp-btn" data-email="{{ $email }}" class="text-sm text-gray-600 hover:text-green-600 hover:underline">Didn't receive code? Resend</button>
        </div>
        @endif
    </div>

<script>
    const resendBtn = document.getElementById('resend-otp-btn');
    if (resendBtn) {
        resendBtn.addEventListener('click', function (e) {
            e.preventDefault();
            this.disabled = true;
            this.textContent = 'Sending...';

            const alertDiv = document.getElementById('alert-message');
            alertDiv.classList.add('hidden');

            fetch("{{ route('register.otp.resend') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: this.dataset.email })
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                let message = body.message;
                alertDiv.textContent = message;

                if (status === 200) {
                    alertDiv.className = 'my-2 text-sm text-center p-2 rounded bg-green-100 text-green-700';
                } else {
                    alertDiv.className = 'my-2 text-sm text-center p-2 rounded bg-red-100 text-red-700';
                }
                alertDiv.classList.remove('hidden');
            })
            .catch(error => {
                alertDiv.textContent = 'An unexpected error occurred. Please try again later.';
                alertDiv.className = 'my-2 text-sm text-center p-2 rounded bg-red-100 text-red-700';
                alertDiv.classList.remove('hidden');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Didn\'t receive code? Resend';
            });
        });
    }
</script>
</body>
</html>