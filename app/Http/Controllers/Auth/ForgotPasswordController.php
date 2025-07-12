<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('pages.auth.password.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp = rand(100000, 999999);

        $user->otp = $otp;
        $user->reset_pswd_time = Carbon::now();
        $user->save();

        Mail::to($user->email)->send(new SendOtpMail($otp));

        return redirect()->route('password.otp.form', ['email' => $user->email])->with('success', 'An OTP has been sent to your email address.');
    }

    public function showOtpForm(Request $request)
    {
        return view('pages.auth.password.otp', ['email' => $request->email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return back()->withErrors(['otp' => 'Invalid OTP.'])->withInput();
        }

        $resetTime = Carbon::parse($user->reset_pswd_time);
        if ($resetTime->addMinutes(10)->isPast()) {
            return back()->withErrors(['otp' => 'OTP has expired. Please request a new one.'])->withInput();
        }

        return redirect()->route('password.reset.form', ['email' => $user->email, 'otp' => $request->otp]);
    }

    public function showResetForm(Request $request)
    {
        return view('pages.auth.password.reset', ['email' => $request->email, 'otp' => $request->otp]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return redirect()->route('password.request')->with('error', 'Invalid request. Please try again.');
        }

        $user->password = Hash::make($request->password);
        $user->otp = null;
        $user->reset_pswd_time = null;
        $user->save();

        return redirect()->route('login')->with('success', 'Your password has been changed successfully.');
    }
}