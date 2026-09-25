<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        $payouts = Auth::user()->payouts()->latest('period_end')->paginate(10);

        $rows = $payouts->getCollection()->map(fn ($p) => [
            'title' => 'STM-'.$p->period_end->format('Y-m'), 'meta' => 'PAY-'.str_pad($p->id, 4, '0', STR_PAD_LEFT).' · '.($p->method ?? '—'),
            'b' => $p->period_end->format('M Y'),
            'c' => '$'.number_format($p->amount_cents / 100, 0),
            'status' => str($p->status)->headline(),
            'tone' => match ($p->status) {
                'paid' => 'ok', 'scheduled' => 'accent', 'on_hold' => 'wait', 'failed' => 'bad', default => 'info'
            },
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('payouts'),
            'title' => 'Earnings & payouts', 'subtitle' => 'Statements, balances and transfers to your account.',
            'stats' => [
                ['k' => 'Next payout', 'v' => '$'.number_format(Auth::user()->payouts()->where('status', 'scheduled')->sum('amount_cents') / 100, 0), 'tone' => 'accent'],
                ['k' => 'Lifetime', 'v' => '$'.number_format(Auth::user()->payouts()->where('status', 'paid')->sum('amount_cents') / 100, 0), 'tone' => 'ok'],
            ],
            'colA' => 'Statement', 'colB' => 'Period', 'colC' => 'Amount',
            'rows' => $rows, 'pagination' => $payouts->links(),
        ]);
    }
}
