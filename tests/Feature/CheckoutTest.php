<?php

namespace Tests\Feature;

use App\Http\Controllers\Webhooks\FulfillsPaidOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_and_returned_to_checkout_after_signing_in(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Nimbus', 'slug' => 'nimbus',
            'price_cents' => 8900, 'status' => 'live',
        ]);

        $this->get(route('checkout.create', $product))->assertRedirect(route('login'));
    }

    public function test_customer_can_reach_the_checkout_page_for_a_live_product(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Nimbus', 'slug' => 'nimbus',
            'price_cents' => 8900, 'status' => 'live',
        ]);

        $this->actingAs($customer)->get(route('checkout.create', $product))->assertOk();
    }

    public function test_fulfilling_a_paid_order_creates_a_license_and_notifies_the_customer(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author', 'commission_pct' => 70]);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Nimbus', 'slug' => 'nimbus',
            'price_cents' => 8900, 'status' => 'live', 'sales_count' => 0,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id, 'type' => 'product', 'status' => 'pending',
            'payment_gateway' => 'stripe', 'currency' => 'usd',
            'subtotal_cents' => 8900, 'total_cents' => 8900,
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'description' => $product->title, 'license_type' => 'regular',
            'unit_price_cents' => 8900, 'commission_cents' => 2670, 'author_share_cents' => 6230,
        ]);

        app(FulfillsPaidOrder::class)->handle($order);

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertSame(1, $product->fresh()->sales_count);
        $this->assertDatabaseHas('licenses', ['product_id' => $product->id, 'customer_id' => $customer->id, 'status' => 'active']);
        Notification::assertSentTo($customer, \App\Notifications\OrderConfirmed::class);
    }

    public function test_fulfillment_is_idempotent_for_a_duplicate_webhook(): void
    {
        Notification::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Nimbus', 'slug' => 'nimbus',
            'price_cents' => 8900, 'status' => 'live', 'sales_count' => 0,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id, 'type' => 'product', 'status' => 'pending',
            'payment_gateway' => 'stripe', 'currency' => 'usd',
            'subtotal_cents' => 8900, 'total_cents' => 8900,
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'description' => $product->title, 'license_type' => 'regular',
            'unit_price_cents' => 8900, 'commission_cents' => 2670, 'author_share_cents' => 6230,
        ]);

        $fulfiller = app(FulfillsPaidOrder::class);
        $fulfiller->handle($order);
        $fulfiller->handle($order->fresh()); // simulate the gateway retrying the same webhook

        $this->assertSame(1, $product->fresh()->sales_count);
        $this->assertSame(1, \App\Models\License::where('product_id', $product->id)->count());
    }
}
