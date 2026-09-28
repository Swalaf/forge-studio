<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approving_a_submission_publishes_the_product(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Orbit Booking Engine', 'slug' => 'orbit-booking-engine',
            'price_cents' => 7900, 'status' => 'in_review', 'current_version' => '0.9.0',
        ]);
        $version = ProductVersion::create([
            'product_id' => $product->id, 'version' => '1.0.0', 'type' => 'new', 'status' => 'pending', 'submitted_at' => now(),
        ]);
        Storage::disk('local')->put('products/'.$product->id.'/1-0-0.zip', 'reviewed release');

        $this->actingAs($admin)
            ->post("/admin/review/{$version->id}/approve", ['publish_as' => 'live'])
            ->assertRedirect(route('admin.review.index'));

        $this->assertSame('live', $product->fresh()->status);
        $this->assertSame('approved', $version->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);
    }

    public function test_admin_cannot_approve_a_release_with_no_private_zip(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Orbit', 'slug' => 'orbit',
            'price_cents' => 7900, 'status' => 'in_review', 'current_version' => '1.0.0',
        ]);
        $version = ProductVersion::create([
            'product_id' => $product->id, 'version' => '1.0.0', 'type' => 'new',
            'status' => 'pending', 'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.review.approve', $version), ['publish_as' => 'live'])
            ->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->assertSame('in_review', $product->fresh()->status);
        $this->assertSame('pending', $version->fresh()->status);
    }
}
