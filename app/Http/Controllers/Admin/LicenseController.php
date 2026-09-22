<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\License;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'active';
        $query = License::with('product', 'customer');
        match ($tab) {
            'expiring' => $query->where('status', 'expiring'),
            'lapsed' => $query->where('status', 'lapsed'),
            'revoked' => $query->where('status', 'revoked'),
            default => $query->where('status', 'active'),
        };
        $licenses = $query->latest()->paginate(10)->withQueryString();

        $rows = $licenses->getCollection()->map(fn ($l) => [
            'title' => $l->license_key, 'meta' => 'Issued '.$l->created_at->format('d M Y'),
            'b' => $l->product->title,
            'c' => $l->support_until?->format('d M Y') ?? '—',
            'status' => str($l->status)->headline(),
            'tone' => match ($l->status) { 'active' => 'ok', 'expiring' => 'wait', 'lapsed' => 'info', 'revoked' => 'bad', default => 'info' },
            'primary' => $l->status === 'revoked'
                ? ['label' => 'Restore', 'url' => route('admin.licenses.renew', $l), 'method' => 'POST']
                : ['label' => 'Revoke', 'url' => route('admin.licenses.revoke', $l), 'method' => 'POST'],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('licenses'),
            'crumb' => 'Commerce', 'title' => 'Licenses & support', 'subtitle' => 'Issued keys, support windows and renewals across every product.',
            'stats' => [
                ['k' => 'Active licenses', 'v' => (string) License::where('status', 'active')->count(), 'tone' => 'ok'],
                ['k' => 'Expiring support', 'v' => (string) License::where('status', 'expiring')->count(), 'tone' => 'wait'],
                ['k' => 'Extended licenses', 'v' => (string) License::where('license_type', 'extended')->count()],
            ],
            'tabs' => collect(['active' => 'Active', 'expiring' => 'Expiring', 'lapsed' => 'Lapsed', 'revoked' => 'Revoked'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'License', 'colB' => 'Product', 'colC' => 'Support until',
            'rows' => $rows, 'pagination' => $licenses->links(),
        ]);
    }

    public function revoke(License $license): RedirectResponse
    {
        $license->update(['status' => 'revoked']);
        AuditLog::record('license.revoked', $license);

        return back()->with('status', 'License revoked.');
    }

    public function renew(License $license): RedirectResponse
    {
        $license->update(['status' => 'active', 'support_until' => now()->addYear()]);
        AuditLog::record('license.renewed', $license);

        return back()->with('status', 'License renewed for one year.');
    }
}
