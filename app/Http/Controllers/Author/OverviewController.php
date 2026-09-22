<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Review;
use App\Models\Ticket;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        $earnings30d = OrderItem::whereHas('product', fn ($q) => $q->where('author_id', $user->id))
            ->where('created_at', '>=', now()->subDays(30))->sum('author_share_cents');
        $sales30d = OrderItem::whereHas('product', fn ($q) => $q->where('author_id', $user->id))
            ->where('created_at', '>=', now()->subDays(30))->count();
        $inReview = ProductVersion::whereHas('product', fn ($q) => $q->where('author_id', $user->id))
            ->where('status', 'pending')->count();
        $avgRating = Product::where('author_id', $user->id)->avg('rating_avg');

        $stats = [
            ['k' => 'Earnings (30d)', 'v' => '$'.number_format($earnings30d / 100, 0), 'tone' => 'ok'],
            ['k' => 'Sales (30d)', 'v' => (string) $sales30d, 'tone' => 'ok'],
            ['k' => 'In review', 'v' => (string) $inReview, 'tone' => $inReview ? 'wait' : 'ok'],
            ['k' => 'Avg rating', 'v' => number_format($avgRating ?? 0, 1), 'tone' => 'ok'],
        ];

        $needsAction = collect();
        ProductVersion::whereHas('product', fn ($q) => $q->where('author_id', $user->id))->where('status', 'changes_requested')
            ->with('product')->take(3)->get()->each(fn ($v) => $needsAction->push([
                'tag' => 'Review', 'title' => $v->product->title.' — changes requested', 'meta' => $v->reviewer_notes ?? '',
                'url' => route('author.submissions.show', $v),
            ]));
        Ticket::whereHas('product', fn ($q) => $q->where('author_id', $user->id))->where('status', 'breaching')
            ->with('product')->take(3)->get()->each(fn ($t) => $needsAction->push([
                'tag' => 'Support', 'title' => $t->subject, 'meta' => $t->product->title.' is breaching SLA',
                'url' => route('author.support.show', $t),
            ]));
        Review::whereHas('product', fn ($q) => $q->where('author_id', $user->id))->whereNull('reply_body')
            ->with('product')->take(3)->get()->each(fn ($r) => $needsAction->push([
                'tag' => 'Reviews', 'title' => 'Review waiting on a reply', 'meta' => $r->product->title,
                'url' => route('author.reviews.index'),
            ]));

        $topProducts = Product::where('author_id', $user->id)->orderByDesc('sales_count')->take(4)->get();
        $maxSales = max(1, optional($topProducts->first())->sales_count ?? 1);

        return view('author.overview', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('overview'),
            'stats' => $stats, 'needsAction' => $needsAction->take(4),
            'topProducts' => $topProducts, 'maxSales' => $maxSales,
            'nextPayout' => $user->payouts()->where('status', 'scheduled')->orderBy('period_end')->first(),
        ]);
    }
}
