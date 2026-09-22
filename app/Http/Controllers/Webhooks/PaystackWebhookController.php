<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackClient $paystack): Response
    {
        if (! $paystack->verifyWebhookSignature($request->getContent(), $request->header('X-Paystack-Signature'))) {
            Log::warning('Paystack webhook signature verification failed.');

            return response('invalid signature', 400);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) === 'charge.success') {
            $reference = $payload['data']['reference'] ?? null;
            $order = $reference ? Order::where('gateway_reference', $reference)->first() : null;

            if ($order && $order->status === 'pending') {
                // Paystack's webhook payload is trusted once the signature checks out, but we still
                // re-verify against their API before fulfilling — belt and braces for money handling.
                $verified = $paystack->verify($reference);
                if (($verified['data']['status'] ?? null) === 'success') {
                    app(FulfillsPaidOrder::class)->handle($order);
                }
            }
        }

        return response('ok', 200);
    }
}
