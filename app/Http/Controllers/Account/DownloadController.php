<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DownloadController extends Controller
{
    public function index(): View
    {
        $licenses = Auth::user()->licenses()->where('status', '!=', 'revoked')->with('product')->paginate(10);

        $rows = $licenses->getCollection()->map(fn ($l) => [
            'title' => $l->product->title, 'meta' => 'Licensed '.$l->created_at->format('d M Y'),
            'b' => 'v'.$l->product->current_version, 'c' => str($l->license_type)->headline(),
            'status' => 'Available', 'tone' => 'ok',
            'primary' => ['label' => 'Download', 'url' => route('account.downloads.download', $l)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('downloads'),
            'title' => 'Downloads', 'subtitle' => 'Latest builds and versions for everything you own.',
            'stats' => [['k' => 'Available files', 'v' => (string) $licenses->total(), 'tone' => 'ok']],
            'colA' => 'Product', 'colB' => 'Version', 'colC' => 'License',
            'rows' => $rows, 'pagination' => $licenses->links(),
        ]);
    }

    public function download(License $license): RedirectResponse
    {
        $this->authorize('view', $license);

        // No real build artifacts are hosted in this environment — this stands in for
        // a signed download URL to the product's latest build.
        return back()->with('status', 'Your download for '.$license->product->title.' has started.');
    }
}
