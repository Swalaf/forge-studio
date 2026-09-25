<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\PaystackClient;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

class OrderController extends Controller
{
    public function __construct(private PaystackClient $paystack) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'all';
        $query = Order::with('customer');
        match ($tab) {
            'product' => $query->where('type', 'product'),
            'service' => $query->where('type', 'service'),
            'refunds' => $query->where('status', 'refunded'),
            'disputes' => $query->where('status', 'disputed'),
            default => null,
        };
        $orders = $query->latest()->paginate(10)->withQueryString();

        $rows = $orders->getCollection()->map(fn ($o) => [
            'title' => $o->order_number, 'meta' => $o->created_at->format('d M Y').' · '.($o->payment_method ?? 'card'),
            'b' => $o->customer->company ?: $o->customer->name,
            'c' => $o->totalFormatted(),
            'status' => str($o->status)->headline(),
            'tone' => match ($o->status) {
                'paid' => 'ok', 'pending' => 'wait', 'refunded' => 'info', 'disputed' => 'bad', default => 'info'
            },
            'primary' => ['label' => 'View', 'url' => route('admin.orders.show', $o)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('orders'),
            'crumb' => 'Commerce', 'title' => 'Orders & refunds', 'subtitle' => 'Product sales, service invoices, disputes and refund requests.',
            'stats' => [
                ['k' => 'Orders (30d)', 'v' => (string) Order::where('created_at', '>=', now()->subDays(30))->count(), 'tone' => 'ok'],
                ['k' => 'Gross revenue', 'v' => '$'.number_format(Order::where('status', 'paid')->sum('total_cents') / 100, 0), 'tone' => 'ok'],
                ['k' => 'Refunds', 'v' => (string) Order::where('status', 'refunded')->count()],
                ['k' => 'Disputes', 'v' => (string) Order::where('status', 'disputed')->count(), 'tone' => Order::where('status', 'disputed')->count() ? 'wait' : 'ok'],
            ],
            'tabs' => collect(['all' => 'All', 'product' => 'Product sales', 'service' => 'Service invoices', 'refunds' => 'Refunds', 'disputes' => 'Disputes'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Order', 'colB' => 'Customer', 'colC' => 'Amount',
            'rows' => $rows, 'pagination' => $orders->links(),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.order-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('orders'),
            'order' => $order->load('items.product', 'customer'),
        ]);
    }

    public function refund(Order $order): RedirectResponse
    {
        if ($order->status !== 'paid') {
            return back()->withErrors(['refund' => 'Only paid orders can be refunded.']);
        }

        // Demo/seeded orders have no real gateway charge behind them — skip straight to the local flip.
        if ($order->gateway_reference && $order->payment_gateway) {
            try {
                match ($order->payment_gateway) {
                    'stripe' => $this->refundViaStripe($order),
                    'paystack' => $this->paystack->refund($order->gateway_reference),
                    default => null,
                };
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors(['refund' => 'The payment gateway rejected the refund: '.$e->getMessage()]);
            }
        }

        $order->update(['status' => 'refunded']);
        AuditLog::record('order.refunded', $order);

        return back()->with('status', $order->order_number.' refunded.');
    }

    private function refundViaStripe(Order $order): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeCheckoutSession::retrieve($order->gateway_reference);
        StripeRefund::create(['payment_intent' => $session->payment_intent]);
    }
}
