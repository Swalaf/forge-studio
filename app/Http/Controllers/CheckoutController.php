<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\PaystackClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct(private PaystackClient $paystack) {}

    public function create(Product $product): View
    {
        abort_unless($product->status === 'live', 404);
        abort_if($product->isAcquisition() && $product->acquisition_status === 'sold', 404);

        return view('market.checkout', ['product' => $product]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'live', 404);
        abort_if($product->isAcquisition() && $product->acquisition_status === 'sold', 404);

        $data = $request->validate([
            'license_type' => [$product->isAcquisition() ? 'nullable' : 'required', 'in:regular,extended,acquisition'],
            'gateway' => ['required', 'in:stripe,paystack'],
        ]);

        if ($product->isAcquisition()) {
            $data['license_type'] = 'acquisition';
        }

        if ($data['license_type'] === 'extended' && ! $product->extended_price_cents) {
            return back()->withErrors(['license_type' => 'Extended license is not available for this product.']);
        }

        $priceCents = match ($data['license_type']) {
            'acquisition' => $product->asking_price_cents ?? $product->price_cents,
            'extended' => $product->extended_price_cents,
            default => $product->price_cents,
        };
        $currency = $data['gateway'] === 'stripe' ? config('services.stripe.currency') : config('services.paystack.currency');
        $commissionPct = 100 - $product->author->commission_pct;

        $order = Order::create([
            'customer_id' => $request->user()->id,
            'type' => $product->isAcquisition() ? 'acquisition' : 'product',
            'status' => 'pending',
            'payment_method' => $data['gateway'],
            'payment_gateway' => $data['gateway'],
            'currency' => $currency,
            'subtotal_cents' => $priceCents,
            'tax_cents' => 0,
            'total_cents' => $priceCents,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'description' => $product->title,
            'license_type' => $data['license_type'],
            'unit_price_cents' => $priceCents,
            'commission_cents' => (int) round($priceCents * $commissionPct / 100),
            'author_share_cents' => (int) round($priceCents * $product->author->commission_pct / 100),
        ]);

        return $data['gateway'] === 'stripe'
            ? $this->redirectToStripe($order, $product)
            : $this->redirectToPaystack($order, $product);
    }

    public function success(Order $order): View
    {
        $this->authorize('view', $order);

        return view('market.checkout-success', ['order' => $order->load('items.product')]);
    }

    private function redirectToStripe(Order $order, Product $product): RedirectResponse
    {
        if (! config('services.stripe.secret')) {
            $order->delete();

            return back()->withErrors(['gateway' => 'Card payments are not configured yet. Please try another payment method or contact us.']);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeCheckoutSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $order->customer->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => $order->currency,
                    'unit_amount' => $order->total_cents,
                    'product_data' => ['name' => $product->title],
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['order_id' => $order->id],
            'success_url' => route('checkout.success', $order),
            'cancel_url' => $product->isAcquisition() ? route('acquisitions.show', $product) : route('market.show', $product),
        ]);

        $order->update(['gateway_reference' => $session->id]);

        return redirect()->away($session->url);
    }

    private function redirectToPaystack(Order $order, Product $product): RedirectResponse
    {
        if (! config('services.paystack.secret_key')) {
            $order->delete();

            return back()->withErrors(['gateway' => 'Paystack is not configured yet. Please try another payment method or contact us.']);
        }

        $reference = 'FM-'.$order->id.'-'.Str::upper(Str::random(8));

        $response = $this->paystack->initialize([
            'email' => $order->customer->email,
            'amount' => $order->total_cents,
            'currency' => $order->currency,
            'reference' => $reference,
            'callback_url' => route('checkout.success', $order),
            'metadata' => ['order_id' => $order->id],
        ]);

        $order->update(['gateway_reference' => $reference]);

        return redirect()->away($response['data']['authorization_url']);
    }
}
