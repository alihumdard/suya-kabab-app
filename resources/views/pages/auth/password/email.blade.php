<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css" />
</head>

<body class="min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-200">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">Forgot Password</h2>
        <p class="text-center text-sm text-gray-600 mb-6">Enter your email and we'll send you a code to reset your
            password.</p>

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

        <form method="POST" action="{{ route('admin.password.email') }}">
            @csrf
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    required value="{{ old('email') }}">
                @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full text-white font-semibold py-3 rounded-lg transition duration-200"
                style="background-color: #3cb371;">
                Send Password Reset Code
            </button>
        </form>
    </div>
</body>

</html>