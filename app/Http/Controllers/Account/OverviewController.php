<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $stats = [
            ['k' => 'Products owned', 'v' => (string) License::where('customer_id', $user->id)->distinct('product_id')->count('product_id'), 'tone' => 'ok'],
            ['k' => 'Active licenses', 'v' => (string) $user->licenses()->where('status', 'active')->count(), 'tone' => 'ok'],
            ['k' => 'Service orders', 'v' => (string) $user->serviceProjects()->count(), 'tone' => 'accent'],
        ];

        return view('account.overview', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('overview'),
            'stats' => $stats,
            'recentLicenses' => $user->licenses()->with('product')->latest()->take(3)->get(),
            'openTickets' => $user->openedTickets()->whereIn('status', ['open', 'waiting', 'breaching'])->count(),
            'activeServices' => $user->serviceProjects()->whereIn('status', ['active', 'at_risk', 'launching'])->count(),
        ]);
    }
}
