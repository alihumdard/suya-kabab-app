<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function subscribe(Request $request, string $priceId)
    {
        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('dashboard'),
                'cancel_url' => route('home'),
            ]);
    }

    public function swapPlan(Request $request, string $priceId)
    {
        $request->user()->subscription('default')->swap($priceId);
        return redirect()->route('dashboard')->with('success', 'Your plan has been changed successfully!');
    }

    public function cancelSubscription(Request $request)
    {
        $request->user()->subscription('default')->cancel();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')->with('success', 'Your subscription has been canceled.');
    }
}