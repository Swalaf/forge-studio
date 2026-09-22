<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_every_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/author')->assertRedirect('/login');
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_a_customer_cannot_reach_the_admin_or_author_dashboards(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $this->actingAs($customer)->get('/author')->assertForbidden();
        $this->actingAs($customer)->get('/account')->assertOk();
    }

    public function test_an_author_cannot_reach_the_admin_or_customer_dashboards(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)->get('/admin')->assertForbidden();
        $this->actingAs($author)->get('/account')->assertForbidden();
        $this->actingAs($author)->get('/author')->assertOk();
    }

    public function test_an_admin_can_reach_every_dashboard_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['/admin', '/admin/review', '/admin/products', '/admin/authors', '/admin/customers',
            '/admin/orders', '/admin/licenses', '/admin/payouts', '/admin/requests', '/admin/projects',
            '/admin/tickets', '/admin/content', '/admin/audit', '/admin/settings'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }
}
