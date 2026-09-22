<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOrderScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_their_own_invoice_but_not_someone_elses(): void
    {
        $me = User::factory()->create(['role' => 'customer']);
        $someoneElse = User::factory()->create(['role' => 'customer']);

        $myOrder = Order::create(['customer_id' => $me->id, 'type' => 'product', 'status' => 'paid', 'subtotal_cents' => 8900, 'total_cents' => 8900]);
        $otherOrder = Order::create(['customer_id' => $someoneElse->id, 'type' => 'product', 'status' => 'paid', 'subtotal_cents' => 3900, 'total_cents' => 3900]);

        $this->actingAs($me)->get("/account/invoices/{$myOrder->id}")->assertOk();
        $this->actingAs($me)->get("/account/invoices/{$otherOrder->id}")->assertForbidden();
    }
}
