<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\License;
use App\Models\Order;
use App\Notifications\OrderConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shared by both gateway webhooks so a product purchase is fulfilled the same
 * way regardless of which processor collected the payment. Wrapped in a
 * row-locked transaction because gateways retry webhook delivery — this
 * makes fulfillment idempotent even if the same event arrives twice.
 */
class FulfillsPaidOrder
{
    public function handle(Order $order): void
    {
        $fulfilled = DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'pending') {
                return false;
            }

            $locked->update(['status' => 'paid', 'paid_at' => now()]);

            foreach ($locked->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                License::create([
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'customer_id' => $locked->customer_id,
                    'license_key' => 'LIC-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
                    'license_type' => $item->license_type ?? 'regular',
                    'support_until' => now()->addYear(),
                    'status' => 'active',
                ]);

                $item->product()->increment('sales_count');
            }

            return true;
        });

        if ($fulfilled) {
            $order->refresh()->customer->notify(new OrderConfirmed($order));
        }
    }
}
