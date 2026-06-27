<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'active' => User::where('account_status', 'active')->count(),
            'pending' => User::where('account_status', 'awaiting_approval')->count(),
            'archived' => User::where('is_archived', true)->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
