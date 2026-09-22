<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductVersion;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewQueueController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value() ?: 'pending';

        $versions = ProductVersion::with('product.author')->where('status', $status)
            ->latest('submitted_at')->paginate(10)->withQueryString();

        $rows = $versions->getCollection()->map(fn ($v) => [
            'title' => $v->product->title.' · v'.$v->version,
            'meta' => $v->type === 'new' ? 'New product' : ucfirst($v->type).' update',
            'b' => $v->product->priceFormatted().' · '.($v->product->category?->name ?? '—'),
            'c' => $v->product->author->name,
            'status' => str($v->status)->headline(),
            'tone' => match ($v->status) {
                'pending' => 'wait', 'approved' => 'ok', 'changes_requested' => 'wait', 'rejected' => 'bad', default => 'info',
            },
            'primary' => ['label' => 'Review', 'url' => route('admin.review.show', $v)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('review'),
            'crumb' => 'Marketplace', 'title' => 'Review queue',
            'subtitle' => 'No third-party product reaches the storefront without a studio engineer signing off.',
            'stats' => [
                ['k' => 'Awaiting review', 'v' => (string) ProductVersion::where('status', 'pending')->count(), 'tone' => 'wait'],
                ['k' => 'Changes requested', 'v' => (string) ProductVersion::where('status', 'changes_requested')->count(), 'tone' => 'wait'],
                ['k' => 'Approved', 'v' => (string) ProductVersion::where('status', 'approved')->count(), 'tone' => 'ok'],
                ['k' => 'Rejected', 'v' => (string) ProductVersion::where('status', 'rejected')->count()],
            ],
            'tabs' => collect(['pending' => 'Needs review', 'changes_requested' => 'Changes sent', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $status === $key])->values(),
            'colA' => 'Submission', 'colB' => 'Price / type', 'colC' => 'Author',
            'rows' => $rows,
            'pagination' => $versions->links(),
            'emptyText' => 'Nothing in this queue.',
        ]);
    }

    public function show(ProductVersion $productVersion): View
    {
        $productVersion->load('product.author');

        return view('admin.review-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('review'),
            'version' => $productVersion,
        ]);
    }

    public function approve(Request $request, ProductVersion $productVersion): RedirectResponse
    {
        $data = $request->validate(['reviewer_notes' => ['nullable', 'string'], 'publish_as' => ['nullable', 'in:live,scheduled,hidden']]);

        $productVersion->update([
            'status' => 'approved', 'reviewed_by' => $request->user()->id,
            'reviewer_notes' => $data['reviewer_notes'] ?? null, 'reviewed_at' => now(),
        ]);

        $productVersion->product->update([
            'status' => $data['publish_as'] ?? 'live',
            'current_version' => $productVersion->version,
            'published_at' => $productVersion->product->published_at ?? now(),
        ]);

        AuditLog::record('product_version.approved', $productVersion, ['product' => $productVersion->product->title]);

        return redirect()->route('admin.review.index')->with('status', 'Approved and published.');
    }

    public function requestChanges(Request $request, ProductVersion $productVersion): RedirectResponse
    {
        $data = $request->validate(['reviewer_notes' => ['required', 'string']]);

        $productVersion->update(['status' => 'changes_requested', 'reviewer_notes' => $data['reviewer_notes'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $productVersion->product->update(['status' => 'changes_requested']);

        AuditLog::record('product_version.changes_requested', $productVersion);

        return redirect()->route('admin.review.index')->with('status', 'Changes requested — the author has been notified.');
    }

    public function reject(Request $request, ProductVersion $productVersion): RedirectResponse
    {
        $data = $request->validate(['reviewer_notes' => ['required', 'string']]);

        $productVersion->update(['status' => 'rejected', 'reviewer_notes' => $data['reviewer_notes'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $productVersion->product->update(['status' => 'rejected']);

        AuditLog::record('product_version.rejected', $productVersion);

        return redirect()->route('admin.review.index')->with('status', 'Submission rejected.');
    }
}
