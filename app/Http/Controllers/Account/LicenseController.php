<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\License;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(): View
    {
        $licenses = Auth::user()->licenses()->with('product')->latest()->paginate(10);

        $rows = $licenses->getCollection()->map(fn ($l) => [
            'title' => $l->license_key, 'meta' => $l->domain ?? 'Not registered',
            'b' => $l->product->title, 'c' => $l->support_until?->format('d M Y') ?? '—',
            'status' => str($l->status)->headline(),
            'tone' => match ($l->status) { 'active' => 'ok', 'expiring' => 'wait', 'lapsed' => 'info', 'revoked' => 'bad', default => 'info' },
            'primary' => in_array($l->status, ['expiring', 'lapsed'])
                ? ['label' => 'Renew', 'url' => route('account.licenses.renew', $l), 'method' => 'POST']
                : null,
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('licenses'),
            'title' => 'Licenses', 'subtitle' => 'Keys, the projects they are used on and their support windows.',
            'stats' => [
                ['k' => 'Active', 'v' => (string) Auth::user()->licenses()->where('status', 'active')->count(), 'tone' => 'ok'],
                ['k' => 'Expiring', 'v' => (string) Auth::user()->licenses()->where('status', 'expiring')->count(), 'tone' => 'wait'],
            ],
            'colA' => 'License key', 'colB' => 'Product', 'colC' => 'Support until',
            'rows' => $rows, 'pagination' => $licenses->links(),
        ]);
    }

    public function renew(License $license): RedirectResponse
    {
        $this->authorize('update', $license);
        $license->update(['status' => 'active', 'support_until' => now()->addYear()]);
        AuditLog::record('license.renewed', $license);

        return back()->with('status', 'License renewed.');
    }
}
