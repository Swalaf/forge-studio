<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{string}>
     */
    public static function signInRoles(): array
    {
        return [
            'customer form' => ['customer'],
            'developer form' => ['author'],
        ];
    }

    #[DataProvider('signInRoles')]
    public function test_admin_signs_in_from_a_regular_sign_in_form(string $role): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->get(route('login', ['role' => $role]))
            ->assertSee('data-initial-role="'.$role.'"', false);

        $this->post(route('login'), [
            'email' => $admin->email, 'password' => 'password',
        ])->assertRedirect(route('admin.overview'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_sign_in_hides_the_admin_choice_even_with_an_admin_role_hint(): void
    {
        $this->get(route('login', ['role' => 'admin']))
            ->assertSee('data-initial-role="customer"', false)
            ->assertSee('data-role="admin" hidden', false);
    }

    public function test_sign_up_keeps_the_existing_admin_access_request_choice(): void
    {
        $this->get(route('register', ['role' => 'admin']))
            ->assertSee('data-initial-role="admin"', false)
            ->assertDontSee('data-role="admin" hidden', false);
    }

    public function test_login_throttles_repeated_attempts_for_the_same_account_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => 'throttle@example.com', 'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), [
            'email' => 'throttle@example.com', 'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }
}
