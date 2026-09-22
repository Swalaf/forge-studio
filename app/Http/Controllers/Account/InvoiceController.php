<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $orders = Auth::user()->orders()->with('items')->latest()->paginate(10);

        $rows = $orders->getCollection()->map(fn ($o) => [
            'title' => 'INV-'.$o->id, 'meta' => $o->items->first()?->description ?? $o->order_number,
            'b' => $o->created_at->format('d M Y'), 'c' => $o->totalFormatted(),
            'status' => str($o->status)->headline(),
            'tone' => $o->status === 'paid' ? 'ok' : 'info',
            'primary' => ['label' => 'View', 'url' => route('account.invoices.show', $o)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('invoices'),
            'title' => 'Invoices', 'subtitle' => 'Billing documents for products, services and renewals.',
            'stats' => [
                ['k' => 'Invoices', 'v' => (string) $orders->total()],
                ['k' => 'Paid', 'v' => '$'.number_format(Auth::user()->orders()->where('status', 'paid')->sum('total_cents') / 100, 0), 'tone' => 'ok'],
            ],
            'colA' => 'Invoice', 'colB' => 'Issued', 'colC' => 'Amount',
            'rows' => $rows, 'pagination' => $orders->links(),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('account.invoice-show', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('invoices'),
            'order' => $order->load('items'),
        ]);
    }
}
