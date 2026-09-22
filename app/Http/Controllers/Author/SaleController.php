<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $items = OrderItem::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))
            ->with('order.customer', 'product')->latest()->paginate(10);

        $rows = $items->getCollection()->map(fn ($i) => [
            'title' => $i->order->order_number, 'meta' => $i->order->created_at->format('d M Y').' · '.($i->order->payment_method ?? 'card'),
            'b' => $i->product?->title ?? $i->description,
            'c' => '$'.number_format($i->author_share_cents / 100, 2),
            'status' => str($i->order->status)->headline(),
            'tone' => match ($i->order->status) { 'paid' => 'ok', 'refunded' => 'bad', default => 'wait' },
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('sales'),
            'title' => 'Sales', 'subtitle' => 'Every transaction, its license type and your share.',
            'stats' => [
                ['k' => 'Units (30d)', 'v' => (string) OrderItem::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->where('created_at', '>=', now()->subDays(30))->count(), 'tone' => 'ok'],
                ['k' => 'Your share (30d)', 'v' => '$'.number_format(OrderItem::whereHas('product', fn ($q) => $q->where('author_id', Auth::id()))->where('created_at', '>=', now()->subDays(30))->sum('author_share_cents') / 100, 0), 'tone' => 'ok'],
            ],
            'colA' => 'Order', 'colB' => 'Product', 'colC' => 'Your share',
            'rows' => $rows, 'pagination' => $items->links(),
        ]);
    }
}
