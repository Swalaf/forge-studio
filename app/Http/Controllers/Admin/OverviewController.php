<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\ServiceProject;
use App\Models\Ticket;
use App\Support\Nav;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        $revenue30d = Order::where('status', 'paid')->where('created_at', '>=', now()->subDays(30))->sum('total_cents');
        $pendingReview = ProductVersion::where('status', 'pending')->count();
        $livePipeline = ServiceProject::whereIn('status', ['active', 'at_risk', 'launching'])->sum('value_cents');
        $openTickets = Ticket::whereIn('status', ['open', 'breaching'])->count();

        $stats = [
            ['k' => 'Revenue (30d)', 'v' => '$'.number_format($revenue30d / 100, 0)],
            ['k' => 'Pending review', 'v' => (string) $pendingReview, 'tone' => $pendingReview ? 'wait' : 'ok'],
            ['k' => 'Live products', 'v' => (string) Product::live()->count(), 'tone' => 'ok'],
            ['k' => 'Service pipeline', 'v' => '$'.number_format($livePipeline / 100, 0)],
            ['k' => 'Open tickets', 'v' => (string) $openTickets, 'tone' => $openTickets ? 'wait' : 'ok'],
        ];

        $decisions = ProductVersion::where('status', 'pending')->with('product.author')->latest('submitted_at')->take(5)->get()
            ->map(fn ($v) => [
                'title' => $v->product->title.' — '.$v->version,
                'meta' => 'Submitted by '.$v->product->author->name,
                'tag' => 'Review',
                'url' => route('admin.review.show', $v),
            ]);

        $topProducts = Product::orderByDesc('sales_count')->take(5)->get();
        $maxSales = max(1, optional($topProducts->first())->sales_count ?? 1);

        return view('admin.overview', [
            'dashTitle' => 'Forge Admin',
            'dashSub' => 'Owner console',
            'navGroups' => Nav::admin('overview'),
            'stats' => $stats,
            'decisions' => $decisions,
            'topProducts' => $topProducts,
            'maxSales' => $maxSales,
            'recentActivity' => AuditLog::with('actor')->latest()->take(6)->get(),
        ]);
    }
}
