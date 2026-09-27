<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_downloads_an_owned_private_release(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $license = $this->createLicense($customer);
        Storage::disk('local')->put($license->product->downloadPath(), 'private release');

        $response = $this->actingAs($customer)->get(route('account.downloads.download', $license));

        $response->assertDownload('nimbus-1-0-0.zip');
        $this->assertSame('private release', $response->streamedContent());
    }

    public function test_other_customers_and_revoked_licenses_cannot_download(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $license = $this->createLicense($customer);
        Storage::disk('local')->put($license->product->downloadPath(), 'private release');

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('account.downloads.download', $license))->assertForbidden();

        $license->update(['status' => 'revoked']);
        $this->actingAs($customer)->get(route('account.downloads.download', $license))->assertForbidden();
    }

    public function test_missing_release_does_not_claim_a_download_started(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $license = $this->createLicense($customer);

        $this->actingAs($customer)->get(route('account.downloads.download', $license))
            ->assertRedirect()->assertSessionHasErrors('download');
    }

    private function createLicense(User $customer): License
    {
        $product = Product::create([
            'author_id' => User::factory()->create(['role' => 'author'])->id,
            'title' => 'Nimbus', 'slug' => 'nimbus', 'price_cents' => 8900, 'status' => 'live', 'current_version' => '1.0.0',
        ]);
        $order = Order::create([
            'customer_id' => $customer->id, 'type' => 'product', 'status' => 'paid',
            'subtotal_cents' => 8900, 'total_cents' => 8900,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'description' => $product->title,
            'license_type' => 'regular', 'unit_price_cents' => 8900,
            'commission_cents' => 2670, 'author_share_cents' => 6230,
        ]);

        return License::create([
            'order_item_id' => $item->id, 'product_id' => $product->id,
            'customer_id' => $customer->id, 'license_key' => 'LIC-TEST-'.$item->id,
            'license_type' => 'regular', 'status' => 'active',
        ]);
    }
}
