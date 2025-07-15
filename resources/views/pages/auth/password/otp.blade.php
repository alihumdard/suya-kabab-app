<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify OTP - Password Reset</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
    </style>
</head>

<body class="bg min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-200">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Verify OTP</h2>
        <p class="text-center text-sm text-gray-600 mb-6">
            A 6-digit verification code has been sent to <strong>{{ $email }}</strong>
        </p>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.password.otp.verify') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="mb-5">
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <input type="text" id="otp" name="otp" placeholder="Enter 6-digit code" maxlength="6"
                    class="w-full px-4 py-3 rounded-lg border @error('otp') border-red-500 @else border-gray-300 @enderror focus:outline-none focus:ring-2 focus:ring-green-500"
                    required value="{{ old('otp') }}">
                @error('otp')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full bg-[#229a76] hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition duration-200">
                Verify Code
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('admin.password.request') }}"
                class="text-sm text-gray-600 hover:text-green-600 hover:underline">
                Back to Password Reset
            </a>
        </div>
    </div>
</body>

</html>