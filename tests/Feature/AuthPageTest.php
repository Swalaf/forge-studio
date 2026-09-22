<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_signing_up_as_customer_creates_a_plain_customer_account(): void
    {
        $this->post(route('register'), [
            'name' => 'Test Customer', 'email' => 'c@example.com', 'password' => 'password123',
            'intended_role' => 'customer', 'terms' => '1',
        ])->assertRedirect(route('account.overview'));

        $user = User::where('email', 'c@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->author_application_status);
    }

    public function test_signing_up_to_sell_never_grants_author_role_directly_but_queues_an_application(): void
    {
        $this->post(route('register'), [
            'name' => 'Aspiring Dev', 'email' => 'dev@example.com', 'password' => 'password123',
            'intended_role' => 'author', 'terms' => '1',
        ])->assertRedirect(route('account.overview'));

        $user = User::where('email', 'dev@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role); // never author directly
        $this->assertSame('pending', $user->author_application_status);
    }

    public function test_requesting_admin_access_never_grants_admin_role_and_opens_a_ticket_instead(): void
    {
        $this->post(route('register'), [
            'name' => 'Wants Access', 'email' => 'wants-access@example.com', 'password' => 'password123',
            'intended_role' => 'admin', 'terms' => '1',
        ])->assertRedirect(route('account.overview'));

        $user = User::where('email', 'wants-access@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role); // never admin, ever, from a public form
        $this->assertDatabaseHas('tickets', ['opener_id' => $user->id, 'subject' => 'Studio console access requested']);
        $this->assertSame(1, Ticket::where('opener_id', $user->id)->count());
    }

    public function test_signup_requires_accepting_terms(): void
    {
        $this->post(route('register'), [
            'name' => 'No Terms', 'email' => 'noterms@example.com', 'password' => 'password123',
            'intended_role' => 'customer',
        ])->assertSessionHasErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'noterms@example.com']);
    }

    public function test_demo_account_panel_is_hidden_outside_local_and_staging(): void
    {
        // The test environment is neither local nor staging, so this exercises the same
        // guard that keeps one-click demo logins off a real production deployment.
        $this->get(route('login'))->assertDontSee('Demo accounts');
    }
}
