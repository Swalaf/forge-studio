<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        $orders = Auth::user()->orders()->with('items.product')->latest()->paginate(10);

        $rows = $orders->getCollection()->map(fn ($o) => [
            'title' => $o->items->first()?->description ?? $o->order_number, 'meta' => $o->order_number.' · '.$o->created_at->format('d M Y'),
            'b' => $o->items->first()?->license_type ? str($o->items->first()->license_type)->headline() : '—',
            'c' => $o->totalFormatted(),
            'status' => str($o->status)->headline(),
            'tone' => match ($o->status) { 'paid' => 'ok', 'refunded' => 'info', 'disputed' => 'bad', default => 'wait' },
            'primary' => ['label' => 'Invoice', 'url' => route('account.invoices.show', $o)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('purchases'),
            'title' => 'Purchases', 'subtitle' => 'Every order, its license type and current state.',
            'stats' => [
                ['k' => 'Orders', 'v' => (string) Auth::user()->orders()->count()],
                ['k' => 'Total spent', 'v' => '$'.number_format(Auth::user()->orders()->where('status', 'paid')->sum('total_cents') / 100, 0)],
            ],
            'colA' => 'Product', 'colB' => 'License', 'colC' => 'Paid',
            'rows' => $rows, 'pagination' => $orders->links(),
        ]);
    }
}
