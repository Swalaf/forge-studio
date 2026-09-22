<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approving_a_submission_publishes_the_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Orbit Booking Engine', 'slug' => 'orbit-booking-engine',
            'price_cents' => 7900, 'status' => 'in_review', 'current_version' => '0.9.0',
        ]);
        $version = ProductVersion::create([
            'product_id' => $product->id, 'version' => '1.0.0', 'type' => 'new', 'status' => 'pending', 'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/admin/review/{$version->id}/approve", ['publish_as' => 'live'])
            ->assertRedirect(route('admin.review.index'));

        $this->assertSame('live', $product->fresh()->status);
        $this->assertSame('approved', $version->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);
    }
}
