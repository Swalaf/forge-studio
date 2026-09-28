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

    public function test_signing_up_as_developer_creates_an_author_account_and_opens_the_author_workspace(): void
    {
        $this->post(route('register'), [
            'name' => 'Aspiring Dev', 'email' => 'dev@example.com', 'password' => 'password123',
            'intended_role' => 'author', 'terms' => '1',
        ])->assertRedirect(route('author.overview'));

        $user = User::where('email', 'dev@example.com')->firstOrFail();
        $this->assertSame('author', $user->role);
        $this->assertNull($user->author_application_status);
        $this->assertAuthenticatedAs($user);
        $this->get(route('author.overview'))->assertOk();
    }

    public function test_developer_signs_in_to_the_author_workspace(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->post(route('login'), [
            'email' => $author->email, 'password' => 'password',
        ])->assertRedirect(route('author.overview'));

        $this->assertAuthenticatedAs($author);
        $this->get(route('author.overview'))->assertOk();
    }

    public function test_public_registration_rejects_an_admin_role_even_when_posted_directly(): void
    {
        $this->post(route('register'), [
            'name' => 'Wants Access', 'email' => 'wants-access@example.com', 'password' => 'password123',
            'intended_role' => 'admin', 'terms' => '1',
        ])->assertSessionHasErrors('intended_role');

        $this->assertDatabaseMissing('users', ['email' => 'wants-access@example.com']);
        $this->assertSame(0, Ticket::count());
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

    public function test_sign_in_has_no_admin_choice_even_with_an_admin_role_hint(): void
    {
        $this->get(route('login', ['role' => 'admin']))
            ->assertSee('data-initial-role="customer"', false)
            ->assertDontSee('data-role="admin"', false);
    }

    public function test_registration_has_no_admin_choice_even_with_an_admin_role_hint(): void
    {
        $this->get(route('register', ['role' => 'admin']))
            ->assertSee('data-initial-role="customer"', false)
            ->assertSee('data-role="author"', false)
            ->assertDontSee('data-role="admin"', false)
            ->assertDontSee('Request console access');
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
