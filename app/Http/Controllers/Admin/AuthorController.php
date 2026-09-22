<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'approved';
        $query = User::query();
        match ($tab) {
            'applications' => $query->where('author_application_status', 'pending'),
            'probation' => $query->where('role', 'author')->where('standing', 'probation'),
            'suspended' => $query->where('role', 'author')->where('standing', 'suspended'),
            default => $query->where('role', 'author')->where('standing', 'good'),
        };
        $authors = $query->withCount('products')->paginate(10)->withQueryString();

        $rows = $authors->getCollection()->map(fn ($a) => [
            'avatarKind' => 'person', 'avatarText' => $a->initials(),
            'title' => $a->name, 'meta' => ($a->country ?? '—').' · joined '.$a->created_at->format('Y'),
            'b' => $a->products_count.' products',
            'c' => '$'.number_format($a->payouts()->sum('amount_cents') / 100, 0),
            'status' => $a->author_application_status === 'pending' ? 'Application' : ($a->author_tier === 'exclusive' ? 'Exclusive' : str($a->standing)->headline()),
            'tone' => match (true) {
                $a->author_application_status === 'pending' => 'wait',
                $a->standing === 'suspended' => 'bad',
                $a->standing === 'probation' => 'wait',
                default => 'ok',
            },
            'primary' => $a->author_application_status === 'pending'
                ? ['label' => 'Approve', 'url' => route('admin.authors.approve', $a), 'method' => 'POST']
                : ['label' => 'Open', 'url' => route('admin.authors.show', $a)],
            'secondary' => ['label' => 'Open', 'url' => route('admin.authors.show', $a)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('authors'),
            'crumb' => 'Marketplace', 'title' => 'Authors', 'subtitle' => 'Approve developers, set commission tiers and watch quality signals.',
            'stats' => [
                ['k' => 'Approved authors', 'v' => (string) User::where('role', 'author')->count(), 'tone' => 'ok'],
                ['k' => 'Applications', 'v' => (string) User::where('author_application_status', 'pending')->count(), 'tone' => 'wait'],
                ['k' => 'Suspended', 'v' => (string) User::where('standing', 'suspended')->count(), 'tone' => 'bad'],
            ],
            'tabs' => collect(['approved' => 'Approved', 'applications' => 'Applications', 'probation' => 'Probation', 'suspended' => 'Suspended'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Author', 'colB' => 'Products', 'colC' => 'Earnings',
            'rows' => $rows, 'pagination' => $authors->links(),
        ]);
    }

    public function show(User $author): View
    {
        return view('admin.author-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('authors'),
            'author' => $author->loadCount('products'),
            'products' => $author->products()->latest()->take(10)->get(),
            'payouts' => $author->payouts()->latest('period_end')->take(6)->get(),
        ]);
    }

    public function approve(User $author): RedirectResponse
    {
        $author->update(['role' => 'author', 'author_application_status' => 'approved', 'standing' => 'good']);
        AuditLog::record('author.approved', $author);

        return back()->with('status', $author->name.' approved as an author.');
    }

    public function suspend(User $author): RedirectResponse
    {
        $author->update(['standing' => 'suspended']);
        AuditLog::record('author.suspended', $author);

        return back()->with('status', $author->name.' suspended.');
    }

    public function updateTier(Request $request, User $author): RedirectResponse
    {
        $data = $request->validate([
            'author_tier' => ['required', 'in:standard,exclusive,studio_original,probation'],
            'commission_pct' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
        $author->update($data);
        AuditLog::record('author.tier_updated', $author, $data);

        return back()->with('status', 'Commission tier updated.');
    }
}
