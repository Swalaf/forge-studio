<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcquisitionMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_acquisition_listing_is_visible_on_public_pages(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'CRM SaaS Acquisition',
            'slug' => 'crm-saas-acquisition',
            'tagline' => 'Revenue-generating CRM project.',
            'description' => 'A full project acquisition.',
            'price_cents' => 2500000,
            'asking_price_cents' => 2500000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'fixed_or_offer',
            'acquisition_status' => 'available',
            'minimum_offer_cents' => 2000000,
            'status' => 'live',
            'published_at' => now(),
        ]);

        $this->get(route('acquisitions.index'))
            ->assertOk()
            ->assertSee('CRM SaaS Acquisition');

        $this->get(route('acquisitions.show', $listing))
            ->assertOk()
            ->assertSee('Revenue-generating CRM project.')
            ->assertSee('Sign in to offer or bid');
    }

    public function test_customer_can_submit_offer_for_offer_listing(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'Offer Listing',
            'slug' => 'offer-listing',
            'price_cents' => 2500000,
            'asking_price_cents' => 2500000,
            'minimum_offer_cents' => 2000000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'offer',
            'acquisition_status' => 'available',
            'status' => 'live',
        ]);

        $this->actingAs($customer)->post(route('acquisitions.offers.store', $listing), [
            'amount' => 21000,
            'message' => 'Ready to close after diligence.',
        ])->assertRedirect();

        $this->assertDatabaseHas('acquisition_offers', [
            'product_id' => $listing->id,
            'buyer_id' => $customer->id,
            'amount_cents' => 2100000,
            'status' => 'pending',
        ]);
    }

    public function test_customer_can_bid_above_current_auction_minimum(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'Auction Listing',
            'slug' => 'auction-listing',
            'price_cents' => 1000000,
            'asking_price_cents' => 1000000,
            'reserve_price_cents' => 900000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'auction',
            'acquisition_status' => 'available',
            'auction_starts_at' => now()->subDay(),
            'auction_ends_at' => now()->addDay(),
            'status' => 'live',
        ]);

        $this->actingAs($customer)->post(route('acquisitions.bids.store', $listing), [
            'amount' => 9000,
        ])->assertRedirect();

        $this->assertDatabaseHas('acquisition_bids', [
            'product_id' => $listing->id,
            'bidder_id' => $customer->id,
            'amount_cents' => 900000,
            'status' => 'winning',
        ]);
    }

    public function test_sold_acquisition_rejects_new_offers_and_bids(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'Sold Listing',
            'slug' => 'sold-listing',
            'price_cents' => 1000000,
            'asking_price_cents' => 1000000,
            'minimum_offer_cents' => 800000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'fixed_or_offer',
            'acquisition_status' => 'sold',
            'status' => 'live',
            'sold_to_user_id' => $customer->id,
            'sold_at' => now(),
        ]);

        $this->actingAs($customer)->post(route('acquisitions.offers.store', $listing), [
            'amount' => 9000,
        ])->assertNotFound();
    }

    public function test_author_can_manage_acquisition_listing_and_accept_offer(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $customer = User::factory()->create(['role' => 'customer']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'Author Listing',
            'slug' => 'author-listing',
            'price_cents' => 1000000,
            'asking_price_cents' => 1000000,
            'minimum_offer_cents' => 800000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'offer',
            'acquisition_status' => 'available',
            'status' => 'live',
        ]);
        $offer = $listing->acquisitionOffers()->create([
            'buyer_id' => $customer->id,
            'amount_cents' => 900000,
            'currency' => 'usd',
        ]);

        $this->actingAs($author)->get(route('author.acquisitions.index'))->assertOk()->assertSee('Author Listing');
        $this->actingAs($author)->post(route('author.acquisitions.offers.respond', $offer), [
            'decision' => 'accepted',
            'seller_response' => 'Accepted.',
        ])->assertRedirect();

        $this->assertSame('accepted', $offer->fresh()->status);
        $this->assertSame('pending_transfer', $listing->fresh()->acquisition_status);
        $this->assertSame($customer->id, $listing->fresh()->sold_to_user_id);
    }

    public function test_admin_can_close_auction_to_highest_bidder(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'author']);
        $customer = User::factory()->create(['role' => 'customer']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'Admin Auction',
            'slug' => 'admin-auction',
            'price_cents' => 1000000,
            'asking_price_cents' => 1000000,
            'reserve_price_cents' => 900000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'auction',
            'acquisition_status' => 'available',
            'auction_starts_at' => now()->subDay(),
            'auction_ends_at' => now()->subHour(),
            'status' => 'live',
        ]);
        $listing->acquisitionBids()->create([
            'bidder_id' => $customer->id,
            'amount_cents' => 950000,
            'currency' => 'usd',
            'status' => 'winning',
            'placed_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.acquisitions.close-auction', $listing))->assertRedirect();

        $this->assertSame('pending_transfer', $listing->fresh()->acquisition_status);
        $this->assertSame($customer->id, $listing->fresh()->sold_to_user_id);
    }

    public function test_customer_dashboard_shows_project_deals(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $author = User::factory()->create(['role' => 'author']);
        $listing = Product::create([
            'author_id' => $author->id,
            'title' => 'Customer Deal',
            'slug' => 'customer-deal',
            'price_cents' => 1000000,
            'asking_price_cents' => 1000000,
            'listing_type' => 'acquisition',
            'acquisition_sale_type' => 'fixed',
            'acquisition_status' => 'pending_transfer',
            'status' => 'live',
            'sold_to_user_id' => $customer->id,
        ]);

        $this->actingAs($customer)->get(route('account.acquisitions.index'))
            ->assertOk()
            ->assertSee('Customer Deal')
            ->assertSee('Pending Transfer');
    }
}
