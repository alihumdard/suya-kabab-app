<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Professional Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css" />
    <style>
        .bg {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
    </style>
</head>

<body class="bg min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-200">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Login</h2>

        <form id="loginForm" method="POST" action="{{ route('admin.login.post') }}" novalidate>
            @csrf

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    required value="{{ old('email') }}" />
                @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    required minlength="6" />
                @error('password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center text-sm text-gray-600">
                    <input type="checkbox" name="remember"
                        class="mr-2 rounded border-gray-300 text-green-600 focus:ring-green-500" />
                    Remember me
                </label>
                <a href="{{ route('admin.password.request') }}" class="text-sm text-[#229a76] hover:underline">Forgot
                    Password?</a>
            </div>

            @if(session('error'))
                <p class="my-2 text-sm text-center text-red-500">{{ session('error') }}</p>
            @endif
            @if(session('success'))
                <p class="my-2 text-sm text-center text-green-500">{{ session('success') }}</p>
            @endif

            <button type="submit"
                class="w-full bg-[#229a76] hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition duration-200">
                Sign In
            </button>

            <p class="text-center text-sm text-gray-600 mt-6">
                Don't have an account?
                <a href="{{ route('admin.register') }}" class="text-[#229a76] hover:underline font-medium">Register</a>
            </p>
        </form>
    </div>

    @if(session('verify_error'))
        <div id="popup-modal"
            class="fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full h-full bg-black bg-opacity-50">
            <div class="relative p-4 w-full max-w-md">
                <div class="relative p-4 text-center bg-white rounded-lg shadow sm:p-5">
                    <button onclick="document.getElementById('popup-modal').style.display='none'" type="button"
                        class="text-gray-400 absolute top-2.5 right-2.5 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <p class="mb-4 text-gray-500">{{ session('verify_error') }}</p>
                    <div class="flex justify-center items-center space-x-4">
                        <a href="{{ route('admin.register.otp.show', ['email' => session('email_for_verification')]) }}"
                            class="py-2 px-3 text-sm font-medium text-center text-white bg-green-600 rounded-lg hover:bg-green-700">Verify
                            Now</a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</body>

</html>