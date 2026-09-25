<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductVersion;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'open';
        $query = ProductVersion::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->with('product');
        match ($tab) {
            'approved' => $query->where('status', 'approved'),
            'rejected' => $query->where('status', 'rejected'),
            default => $query->whereIn('status', ['pending', 'changes_requested']),
        };
        $versions = $query->latest('submitted_at')->paginate(10)->withQueryString();

        $rows = $versions->getCollection()->map(fn ($v) => [
            'title' => $v->product->title.' v'.$v->version, 'meta' => str($v->type)->headline().' · '.$v->submitted_at?->format('d M Y'),
            'b' => $v->type === 'new' ? 'New product' : 'Version update',
            'c' => $v->submitted_at?->format('d M Y'),
            'status' => str($v->status)->headline(),
            'tone' => match ($v->status) {
                'approved' => 'ok', 'pending' => 'wait', 'changes_requested' => 'wait', 'rejected' => 'bad', default => 'info'
            },
            'primary' => ['label' => 'Open', 'url' => route('author.submissions.show', $v)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('submissions'),
            'title' => 'Submissions', 'subtitle' => 'Nothing publishes until a Forge Studio engineer approves it.',
            'stats' => [
                ['k' => 'In review', 'v' => (string) $query->clone()->where('status', 'pending')->count(), 'tone' => 'wait'],
            ],
            'tabs' => collect(['open' => 'Open', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Submission', 'colB' => 'Type', 'colC' => 'Submitted',
            'rows' => $rows, 'pagination' => $versions->links(),
        ]);
    }

    public function show(ProductVersion $productVersion): View
    {
        $this->authorize('update', $productVersion->product);

        return view('author.submission-show', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('submissions'),
            'version' => $productVersion->load('product', 'reviewer'),
        ]);
    }

    public function withdraw(ProductVersion $productVersion): RedirectResponse
    {
        $this->authorize('update', $productVersion->product);

        $productVersion->update(['status' => 'rejected', 'reviewer_notes' => 'Withdrawn by author.']);
        $productVersion->product->update(['status' => $productVersion->product->published_at ? 'live' : 'draft']);
        AuditLog::record('product_version.withdrawn', $productVersion);

        return redirect()->route('author.submissions.index')->with('status', 'Submission withdrawn.');
    }
}
