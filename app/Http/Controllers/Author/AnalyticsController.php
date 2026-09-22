<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $products = Product::where('author_id', Auth::id())->get();

        $rows = $products->map(fn ($p) => [
            'title' => $p->title, 'meta' => 'PRD-'.str_pad($p->id, 4, '0', STR_PAD_LEFT),
            'b' => number_format($p->view_count), 'c' => $p->view_count ? number_format($p->sales_count / max(1, $p->view_count) * 100, 1).'%' : '—',
            'status' => $p->view_count > 0 && ($p->sales_count / max(1, $p->view_count)) > 0.02 ? 'Strong' : 'Average',
            'tone' => $p->view_count > 0 && ($p->sales_count / max(1, $p->view_count)) > 0.02 ? 'ok' : 'info',
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('analytics'),
            'title' => 'Analytics', 'subtitle' => 'Traffic, conversion and what buyers do before they buy.',
            'stats' => [
                ['k' => 'Page views', 'v' => number_format($products->sum('view_count')), 'tone' => 'ok'],
                ['k' => 'Total sales', 'v' => number_format($products->sum('sales_count')), 'tone' => 'ok'],
            ],
            'colA' => 'Product', 'colB' => 'Views', 'colC' => 'Conversion',
            'rows' => $rows,
        ]);
    }
}
