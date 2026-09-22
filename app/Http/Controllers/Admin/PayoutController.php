<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payout;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'scheduled';
        $payouts = Payout::with('author')->where('status', $tab)->latest('period_end')->paginate(10)->withQueryString();

        $rows = $payouts->getCollection()->map(fn ($p) => [
            'avatarKind' => 'person', 'avatarText' => $p->author->initials(),
            'title' => $p->author->name, 'meta' => 'PAY-'.str_pad($p->id, 4, '0', STR_PAD_LEFT).' · '.($p->method ?? '—'),
            'b' => $p->period_end->format('M Y'),
            'c' => '$'.number_format($p->amount_cents / 100, 0),
            'status' => str($p->status)->headline(),
            'tone' => match ($p->status) { 'paid' => 'ok', 'scheduled' => 'info', 'on_hold' => 'wait', 'failed' => 'bad', default => 'info' },
            'primary' => $p->status === 'on_hold'
                ? ['label' => 'Release', 'url' => route('admin.payouts.release', $p), 'method' => 'POST']
                : ($p->status === 'scheduled' ? ['label' => 'Hold', 'url' => route('admin.payouts.hold', $p), 'method' => 'POST'] : null),
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('payouts'),
            'crumb' => 'Commerce', 'title' => 'Payouts & finance', 'subtitle' => 'Author earnings, studio net and the monthly payout batch.',
            'stats' => [
                ['k' => 'Due to authors', 'v' => '$'.number_format(Payout::where('status', 'scheduled')->sum('amount_cents') / 100, 0), 'tone' => 'wait'],
                ['k' => 'On hold', 'v' => '$'.number_format(Payout::where('status', 'on_hold')->sum('amount_cents') / 100, 0)],
                ['k' => 'Paid (lifetime)', 'v' => '$'.number_format(Payout::where('status', 'paid')->sum('amount_cents') / 100, 0), 'tone' => 'ok'],
            ],
            'tabs' => collect(['scheduled' => 'Scheduled', 'on_hold' => 'On hold', 'paid' => 'Paid', 'failed' => 'Failed'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Payee', 'colB' => 'Period', 'colC' => 'Amount',
            'rows' => $rows, 'pagination' => $payouts->links(),
            'actionsHtml' => '<form method="POST" action="'.route('admin.payouts.run-batch').'">'.csrf_field().'<button class="btn btn-dark" type="submit">Run payout batch</button></form>',
        ]);
    }

    public function runBatch(): RedirectResponse
    {
        $count = Payout::where('status', 'scheduled')->count();
        Payout::where('status', 'scheduled')->update(['status' => 'paid', 'paid_at' => now()]);
        AuditLog::record('payout.batch_run', null, ['count' => $count]);

        return back()->with('status', "Paid out {$count} authors.");
    }

    public function hold(Payout $payout): RedirectResponse
    {
        $payout->update(['status' => 'on_hold']);
        AuditLog::record('payout.held', $payout);

        return back()->with('status', 'Payout put on hold.');
    }

    public function release(Payout $payout): RedirectResponse
    {
        $payout->update(['status' => 'scheduled']);
        AuditLog::record('payout.released', $payout);

        return back()->with('status', 'Payout released back to the schedule.');
    }
}
