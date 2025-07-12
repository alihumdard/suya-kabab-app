<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use Carbon\Carbon;
use Exception;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function index()
    {
        return view('pages.welcome');
    }

    public function showLoginForm()
    {
        return view('pages.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        
        $user = User::where('email', $credentials['email'])->first();

        if ($user && !$user->email_verified_at) {
            return back()
                ->with('verify_error', 'Please verify your email address before logging in.')
                ->with('email_for_verification', $user->email);
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect()->intended('admin/dashboard');
            }
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        return view('pages.auth.signup');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => '2',
            'otp' => $otp,
            'reset_pswd_time' => Carbon::now(),
        ]);

        Mail::to($user->email)->send(new SendOtpMail($otp));

        return redirect()->route('register.otp.show')->with('email', $user->email);
    }

    public function showOtpForm(Request $request)
    {
        $email = $request->email ?? session('email_for_verification') ?? session('email');
        if (!$email) {
            return redirect()->route('register');
        }
        return view('pages.auth.password.otp', ['email' => $email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return back()->withErrors(['otp' => 'The OTP you entered is invalid.'])->withInput();
        }

        $otpSentTime = Carbon::parse($user->reset_pswd_time);
        if ($otpSentTime->addMinutes(10)->isPast()) {
            return back()->withErrors(['otp' => 'The OTP has expired. Please request a new one.'])->withInput();
        }

        $user->email_verified_at = Carbon::now();
        $user->status = '1';
        $user->otp = null;
        $user->reset_pswd_time = null;
        $user->save();

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->first();

        if ($user->last_otp_attempt_at && Carbon::parse($user->last_otp_attempt_at)->addMinutes(10)->isFuture()) {
             return response()->json(['message' => 'Please wait 10 minutes before requesting another OTP.'], 429);
        }

        $attemptCount = (int) $user->reset_pswd_attempt;
        if ($attemptCount >= 3) {
            $user->last_otp_attempt_at = Carbon::now();
            $user->reset_pswd_attempt = '0';
            $user->save();
            return response()->json(['message' => 'You have reached the maximum resend limit. Please wait 10 minutes.'], 429);
        }

        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->reset_pswd_time = Carbon::now();
        $user->reset_pswd_attempt = $attemptCount + 1;
        $user->save();

        Mail::to($user->email)->send(new SendOtpMail($otp));
        
        return response()->json(['message' => 'A new OTP has been sent to your email.']);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = User::updateOrCreate(
                ['google_id' => $googleUser->id],
                [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'email_verified_at' => Carbon::now(),
                    'status' => '1',
                    'role' => 'user'
                ]
            );

            Auth::login($user);
            return redirect()->intended('dashboard');
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Something went wrong with Google Login. Please try again.']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}