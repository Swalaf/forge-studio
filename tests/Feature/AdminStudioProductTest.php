<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminStudioProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_products_page_links_to_the_new_studio_product_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertSee('New studio product')
            ->assertSee(route('admin.products.create'));

        $this->get(route('admin.products.create'))
            ->assertSee(route('admin.products.store'))
            ->assertSee('Private release ZIP');
    }

    public function test_admin_creates_a_studio_original_draft_with_a_private_release(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAuthor = User::factory()->create(['role' => 'author']);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'title' => 'Studio Toolkit', 'price_cents' => 8900, 'extended_price_cents' => 12900,
            'author_id' => $otherAuthor->id, 'is_studio_original' => false, 'status' => 'live', 'is_featured' => 1,
            'release_zip' => UploadedFile::fake()->create('release.zip', 10, 'application/zip'),
        ]);

        $product = Product::sole();
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertSame($admin->id, $product->author_id);
        $this->assertTrue($product->is_studio_original);
        $this->assertTrue($product->is_featured);
        $this->assertSame('draft', $product->status);
        $this->assertSame('1.0.0', $product->current_version);
        $this->assertSame(12900, $product->extended_price_cents);
        Storage::disk('local')->assertExists('products/'.$product->id.'/1-0-0.zip');
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'product.created', 'subject_id' => $product->id]);
    }

    public function test_studio_product_cannot_be_published_without_its_release(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->studioProduct($admin);

        $this->actingAs($admin)->post(route('admin.products.publish', $product))
            ->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->assertSame('draft', $product->fresh()->status);
        $this->assertNull($product->fresh()->published_at);
    }

    public function test_studio_product_cannot_be_made_live_by_editing_without_its_release(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->studioProduct($admin);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'title' => $product->title, 'price_cents' => 8900, 'status' => 'live',
        ])->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->assertSame('draft', $product->fresh()->status);
    }

    public function test_admin_can_upload_a_release_and_publish_a_studio_product(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->studioProduct($admin);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'title' => $product->title, 'price_cents' => 8900, 'status' => 'live',
            'release_zip' => UploadedFile::fake()->create('release.zip', 10, 'application/zip'),
        ])->assertRedirect(route('admin.products.index'));

        $this->assertSame('live', $product->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);
        Storage::disk('local')->assertExists('products/'.$product->id.'/1-0-0.zip');
        $this->get(route('market.show', $product->slug))->assertSee('Forge Studio');
    }

    public function test_admin_can_publish_an_uploaded_studio_product_from_the_products_list(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->studioProduct($admin);
        Storage::disk('local')->put($product->downloadPath(), 'private release');

        $this->actingAs($admin)->post(route('admin.products.publish', $product))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame('live', $product->fresh()->status);
        $this->assertNotNull($product->fresh()->published_at);
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('checkout.create', $product->slug))->assertOk();
    }

    public function test_invalid_release_is_rejected_without_creating_a_product(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'title' => 'Studio Toolkit', 'price_cents' => 8900,
            'release_zip' => UploadedFile::fake()->create('not-a-release.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('release_zip');

        $this->assertSame(0, Product::count());
    }

    public function test_studio_release_upload_cannot_replace_a_third_party_product_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['role' => 'author']);
        $product = Product::create([
            'author_id' => $author->id, 'title' => 'Third-party Toolkit', 'slug' => 'third-party-toolkit',
            'price_cents' => 8900, 'status' => 'draft',
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'title' => 'Changed Title', 'price_cents' => 8900, 'status' => 'draft',
            'release_zip' => UploadedFile::fake()->create('release.zip', 10, 'application/zip'),
        ])->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->assertSame('Third-party Toolkit', $product->fresh()->title);
        Storage::disk('local')->assertMissing($product->downloadPath());
    }

    public function test_author_cannot_create_studio_original_products(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)->get(route('admin.products.create'))->assertForbidden();
        $this->post(route('admin.products.store'), [
            'title' => 'Unauthorized Toolkit', 'price_cents' => 8900,
        ])->assertForbidden();

        $this->assertSame(0, Product::count());
    }

    private function studioProduct(User $admin): Product
    {
        return Product::create([
            'author_id' => $admin->id, 'title' => 'Studio Toolkit', 'slug' => 'studio-toolkit',
            'price_cents' => 8900, 'current_version' => '1.0.0', 'status' => 'draft',
            'is_studio_original' => true,
        ]);
    }
}
