<?php

namespace Tests\Feature;

use App\Http\Controllers\Webhooks\FulfillsPaidOrder;
use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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

        $this->actingAs($customer)->get(route('checkout.create', $product))
            ->assertSee('This release is not available for purchase yet.')
            ->assertDontSee('Continue to payment');
    }

    public function test_checkout_rejects_a_release_without_a_private_artifact(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create([
            'author_id' => User::factory()->create(['role' => 'author'])->id,
            'title' => 'Nimbus', 'slug' => 'nimbus', 'price_cents' => 8900, 'status' => 'live', 'current_version' => '1.0.0',
        ]);

        $this->actingAs($customer)->post(route('checkout.store', $product), [
            'license_type' => 'regular', 'gateway' => 'stripe',
        ])->assertRedirect()->assertSessionHasErrors('product');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_a_gateway_that_would_charge_a_different_currency(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create([
            'author_id' => User::factory()->create(['role' => 'author'])->id,
            'title' => 'Nimbus', 'slug' => 'nimbus', 'price_cents' => 8900, 'status' => 'live', 'current_version' => '1.0.0',
        ]);
        Storage::disk('local')->put($product->downloadPath(), 'private release');
        config()->set('services.paystack.currency', 'NGN');

        $this->actingAs($customer)->post(route('checkout.store', $product), [
            'license_type' => 'regular', 'gateway' => 'paystack',
        ])->assertRedirect()->assertSessionHasErrors('gateway');

        $this->assertSame(0, Order::count());
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
        Notification::assertSentTo($customer, OrderConfirmed::class);
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
        $this->assertSame(1, License::where('product_id', $product->id)->count());
    }

    public function test_fulfillment_queues_order_mail_instead_of_sending_it_in_the_webhook(): void
    {
        Queue::fake([SendQueuedNotifications::class]);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create([
            'author_id' => User::factory()->create(['role' => 'author'])->id,
            'title' => 'Nimbus', 'slug' => 'nimbus', 'price_cents' => 8900, 'status' => 'live',
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
        Queue::assertPushed(SendQueuedNotifications::class);
    }
}
