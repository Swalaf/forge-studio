<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function index(): View
    {
        $licenses = Auth::user()->licenses()->where('status', '!=', 'revoked')->with('product')->paginate(10);

        $rows = $licenses->getCollection()->map(function (License $license): array {
            $available = Storage::disk('local')->exists($license->product->downloadPath());

            return [
                'title' => $license->product->title, 'meta' => 'Licensed '.$license->created_at->format('d M Y'),
                'b' => 'v'.$license->product->current_version, 'c' => str($license->license_type)->headline(),
                'status' => $available ? 'Available' : 'Unavailable', 'tone' => $available ? 'ok' : 'wait',
                'primary' => $available ? ['label' => 'Download', 'url' => route('account.downloads.download', $license)] : null,
            ];
        });

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('downloads'),
            'title' => 'Downloads', 'subtitle' => 'Latest builds and versions for everything you own.',
            'stats' => [['k' => 'Licenses', 'v' => (string) $licenses->total(), 'tone' => 'ok']],
            'colA' => 'Product', 'colB' => 'Version', 'colC' => 'License',
            'rows' => $rows, 'pagination' => $licenses->links(),
        ]);
    }

    public function download(License $license): RedirectResponse|StreamedResponse
    {
        $this->authorize('view', $license);

        abort_if($license->status === 'revoked', 403);

        $product = $license->product;
        $path = $product->downloadPath();

        if (! Storage::disk('local')->exists($path)) {
            return back()->withErrors(['download' => 'This release is not available yet. Please contact support.']);
        }

        return Storage::disk('local')->download($path, Str::slug($product->title).'-'.basename($path));
    }
}
