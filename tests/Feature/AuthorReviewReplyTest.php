<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorReviewReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_reply_to_a_review_on_their_own_product(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create(['author_id' => $author->id, 'title' => 'Slate Form Builder', 'slug' => 'slate-form-builder', 'price_cents' => 4500, 'status' => 'live']);
        $review = Review::create(['product_id' => $product->id, 'customer_id' => $customer->id, 'rating' => 4, 'body' => 'Great builder.']);

        $this->actingAs($author)
            ->post("/author/reviews/{$review->id}/reply", ['reply_body' => 'Thanks for the feedback!'])
            ->assertRedirect();

        $this->assertSame('Thanks for the feedback!', $review->fresh()->reply_body);
    }

    public function test_author_cannot_reply_to_a_review_on_someone_elses_product(): void
    {
        $owner = User::factory()->create(['role' => 'author']);
        $otherAuthor = User::factory()->create(['role' => 'author']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::create(['author_id' => $owner->id, 'title' => 'Tally Recurring Billing', 'slug' => 'tally-recurring-billing', 'price_cents' => 6500, 'status' => 'live']);
        $review = Review::create(['product_id' => $product->id, 'customer_id' => $customer->id, 'rating' => 5, 'body' => 'Solid.']);

        $this->actingAs($otherAuthor)
            ->post("/author/reviews/{$review->id}/reply", ['reply_body' => 'Not mine to answer.'])
            ->assertForbidden();

        $this->assertNull($review->fresh()->reply_body);
    }
}
