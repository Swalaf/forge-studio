<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_cannot_mark_payouts_paid_without_a_real_transfer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'author']);
        $payout = Payout::create([
            'author_id' => $author->id, 'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(), 'amount_cents' => 5000, 'status' => 'scheduled',
        ]);

        $this->actingAs($admin)->post(route('admin.payouts.run-batch'))
            ->assertRedirect()->assertSessionHasErrors('payouts');

        $this->assertSame('scheduled', $payout->fresh()->status);
        $this->assertNull($payout->fresh()->paid_at);
        $this->actingAs($admin)->get(route('admin.payouts.index'))->assertDontSee('Run payout batch');
    }
}
