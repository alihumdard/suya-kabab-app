<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.dashboard');
    }

    public function adminDashboard()
    {
        return view('pages.admin_dashboard');
    }
}
