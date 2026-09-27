<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_receives_configured_password_without_exposing_it_in_output(): void
    {
        $password = 'private-test-password-123';
        config()->set('app.initial_admin_password', $password);

        $this->assertSame(0, Artisan::call('db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]));

        $admin = User::where('role', 'admin')->sole();
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertStringNotContainsString($password, Artisan::output());
    }

    public function test_existing_admin_password_is_not_changed_by_seeding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $originalHash = $admin->password;
        config()->set('app.initial_admin_password', 'private-test-password-123');

        app(ProductionSeeder::class)->run();

        $this->assertSame($originalHash, $admin->fresh()->password);
        $this->assertSame(1, User::where('role', 'admin')->count());
    }
}
