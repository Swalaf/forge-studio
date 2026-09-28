<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_tabs_link_to_filtered_products_without_leaking_other_authors_products(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $other = User::factory()->create(['role' => 'author']);
        $products = [];

        foreach (['live', 'draft', 'in_review', 'changes_requested', 'hidden', 'rejected', 'scheduled'] as $status) {
            $products[$status] = $this->makeProduct($author, $status);
        }

        $otherProduct = $this->makeProduct($other, 'draft');

        foreach ([
            'live' => ['live'],
            'draft' => ['draft'],
            'in_review' => ['in_review', 'changes_requested'],
            'unpublished' => ['hidden', 'rejected', 'scheduled'],
        ] as $tab => $visibleStatuses) {
            $response = $this->actingAs($author)->get(route('author.products.index', ['tab' => $tab]))
                ->assertOk()->assertSee('aria-current="page"', false);

            foreach (['live', 'draft', 'in_review', 'unpublished'] as $link) {
                $response->assertSee(route('author.products.index', ['tab' => $link]));
            }

            foreach ($products as $status => $product) {
                if (in_array($status, $visibleStatuses, true)) {
                    $response->assertSee($product->title)->assertSee(route('author.products.edit', $product));
                } else {
                    $response->assertDontSee($product->title);
                }
            }

            $response->assertDontSee($otherProduct->title);
        }
    }

    public function test_author_creates_draft_with_private_zip_then_edits_its_details_and_release(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)->get(route('author.products.create'))
            ->assertOk()->assertSee('name="release_zip"', false)
            ->assertSee('enctype="multipart/form-data"', false);

        $this->post(route('author.products.store'), [
            'title' => 'Orbit Kit', 'price_cents' => 2900,
            'release_zip' => UploadedFile::fake()->create('orbit.zip', 10, 'application/zip'),
        ])->assertRedirect();

        $product = Product::sole();
        $this->assertSame($author->id, $product->author_id);
        $this->assertSame('draft', $product->status);
        Storage::disk('local')->assertExists('products/'.$product->id.'/1-0-0.zip');

        $this->get(route('author.products.edit', $product))->assertOk()->assertSee('Orbit Kit');
        $this->put(route('author.products.update', $product), [
            'title' => 'Orbit Kit Updated', 'price_cents' => 3900,
            'release_zip' => UploadedFile::fake()->create('revised.zip', 10, 'application/zip'),
        ])->assertRedirect(route('author.products.edit', $product));

        $this->assertSame('Orbit Kit Updated', $product->fresh()->title);
        $this->assertSame(3900, $product->fresh()->price_cents);
        Storage::disk('local')->assertExists($product->downloadPath());
    }

    public function test_author_banner_is_visible_on_the_marketplace_and_can_be_replaced(): void
    {
        Storage::fake('public');
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)->get(route('author.products.create'))
            ->assertOk()->assertSee('name="banner_image"', false);

        $this->post(route('author.products.store'), [
            'title' => 'Orbit Kit', 'price_cents' => 2900,
            'banner_image' => UploadedFile::fake()->image('orbit.png', 1200, 675),
        ])->assertRedirect();

        $product = Product::sole();
        $originalPath = $product->banner->path;
        $this->assertSame('thumbnail', $product->banner->kind);
        Storage::disk('public')->assertExists($originalPath);

        $product->update(['status' => 'live', 'published_at' => now(), 'is_featured' => true]);
        $originalUrl = Storage::disk('public')->url($originalPath);
        $this->get(route('market.show', $product->slug))
            ->assertSee($originalUrl)->assertSee('alt="Preview of Orbit Kit"', false);
        $this->get(route('market.browse'))->assertSee($originalUrl);
        $this->get(route('market.browse', ['view' => 'list']))->assertSee($originalUrl);
        $this->get(route('home'))->assertSee($originalUrl);

        $customer = User::factory()->create(['role' => 'customer']);
        $customer->savedItems()->create(['product_id' => $product->id]);
        $this->actingAs($customer)->get(route('account.saved.index'))->assertSee($originalUrl);

        $this->actingAs($author)->put(route('author.products.update', $product), [
            'title' => $product->title, 'price_cents' => 2900,
            'banner_image' => UploadedFile::fake()->image('new-preview.jpg', 1200, 675),
        ])->assertRedirect(route('author.products.edit', $product));

        $replacementPath = $product->fresh()->banner->path;
        $this->assertNotSame($originalPath, $replacementPath);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($replacementPath);
        $this->assertSame(1, $product->media()->count());
        $this->get(route('market.show', $product->slug))
            ->assertSee(Storage::disk('public')->url($replacementPath))
            ->assertDontSee($originalUrl);
    }

    public function test_invalid_banner_images_do_not_create_an_author_listing(): void
    {
        Storage::fake('public');
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)->post(route('author.products.store'), [
            'title' => 'Unsafe Banner', 'price_cents' => 2900,
            'banner_image' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
        ])->assertRedirect()->assertSessionHasErrors('banner_image');

        $this->post(route('author.products.store'), [
            'title' => 'Too Small', 'price_cents' => 2900,
            'banner_image' => UploadedFile::fake()->image('too-small.png', 100, 100),
        ])->assertRedirect()->assertSessionHasErrors('banner_image');

        $this->post(route('author.products.store'), [
            'title' => 'Executable', 'price_cents' => 2900,
            'banner_image' => UploadedFile::fake()->create('unsafe.php', 10, 'image/png'),
        ])->assertRedirect()->assertSessionHasErrors('banner_image');

        $this->assertSame(0, Product::count());
    }

    public function test_submission_requires_private_zip_and_does_not_advance_without_one(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['role' => 'author']);
        $product = $this->makeProduct($author, 'draft');

        $this->actingAs($author)->post(route('author.products.submit-version', $product), [
            'version' => '1.0.0', 'type' => 'new',
        ])->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->assertSame('draft', $product->fresh()->status);
        $this->assertSame(0, $product->versions()->count());
    }

    public function test_author_can_submit_a_zip_previously_uploaded_with_a_draft(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['role' => 'author']);
        $product = $this->makeProduct($author, 'draft');
        Storage::disk('local')->put($product->downloadPath(), 'draft release');

        $this->actingAs($author)->post(route('author.products.submit-version', $product), [
            'version' => '1.0.0', 'type' => 'new',
        ])->assertRedirect(route('author.submissions.index'));

        $this->assertSame('in_review', $product->fresh()->status);
        $this->assertSame('draft release', Storage::disk('local')->get($product->downloadPath()));
        $this->assertDatabaseHas('product_versions', ['product_id' => $product->id, 'version' => '1.0.0', 'status' => 'pending']);
    }

    public function test_author_submits_version_zip_without_replacing_live_release(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['role' => 'author']);
        $product = $this->makeProduct($author, 'live');
        $product->update(['published_at' => now(), 'current_version' => '1.0.0']);
        Storage::disk('local')->put($product->downloadPath(), 'live release');

        $this->actingAs($author)->post(route('author.products.submit-version', $product), [
            'version' => '1.1.0', 'type' => 'minor', 'changelog' => 'Added charts',
            'release_zip' => UploadedFile::fake()->create('orbit-next.zip', 10, 'application/zip'),
        ])->assertRedirect(route('author.submissions.index'));

        $this->assertSame('in_review', $product->fresh()->status);
        $this->assertSame('1.0.0', $product->fresh()->current_version);
        $this->assertSame('live release', Storage::disk('local')->get($product->downloadPath()));
        Storage::disk('local')->assertExists('products/'.$product->id.'/1-1-0.zip');
        $this->assertDatabaseHas('product_versions', ['product_id' => $product->id, 'version' => '1.1.0', 'status' => 'pending']);
    }

    public function test_published_zip_cannot_be_overwritten_by_editing_or_resubmitting_its_version(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['role' => 'author']);
        $product = $this->makeProduct($author, 'live');
        $product->update(['published_at' => now(), 'current_version' => '1.0.0']);
        Storage::disk('local')->put($product->downloadPath(), 'live release');

        $this->actingAs($author)->put(route('author.products.update', $product), [
            'title' => 'Changed', 'price_cents' => 2900,
            'release_zip' => UploadedFile::fake()->create('replacement.zip', 10, 'application/zip'),
        ])->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->post(route('author.products.submit-version', $product), [
            'version' => '1.0.0', 'type' => 'patch',
            'release_zip' => UploadedFile::fake()->create('replacement.zip', 10, 'application/zip'),
        ])->assertRedirect()->assertSessionHasErrors('version');

        $this->assertSame('live release', Storage::disk('local')->get($product->downloadPath()));
        $this->assertSame('live', $product->fresh()->status);
        $this->assertSame(0, $product->versions()->count());
    }

    public function test_author_cannot_edit_or_upload_to_another_authors_product(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $author = User::factory()->create(['role' => 'author']);
        $otherProduct = $this->makeProduct(User::factory()->create(['role' => 'author']), 'draft');

        $this->actingAs($author)->get(route('author.products.edit', $otherProduct))->assertForbidden();
        $this->put(route('author.products.update', $otherProduct), [
            'title' => 'Changed', 'price_cents' => 2900,
            'release_zip' => UploadedFile::fake()->create('unauthorized.zip', 10, 'application/zip'),
            'banner_image' => UploadedFile::fake()->image('unauthorized.png', 1200, 675),
        ])->assertForbidden();

        $this->assertSame('draft', $otherProduct->fresh()->status);
        Storage::disk('local')->assertMissing($otherProduct->downloadPath());
        $this->assertSame(0, $otherProduct->media()->count());
    }

    public function test_non_zip_upload_is_rejected_before_draft_creation(): void
    {
        Storage::fake('local');
        $author = User::factory()->create(['role' => 'author']);

        $this->actingAs($author)->post(route('author.products.store'), [
            'title' => 'Unsafe', 'price_cents' => 2900,
            'release_zip' => UploadedFile::fake()->create('unsafe.txt', 10, 'text/plain'),
        ])->assertRedirect()->assertSessionHasErrors('release_zip');

        $this->assertSame(0, Product::count());
    }

    private function makeProduct(User $author, string $status): Product
    {
        return Product::create([
            'author_id' => $author->id,
            'title' => $author->name.' Product '.str_replace('_', '-', $status),
            'slug' => $author->id.'-'.str_replace('_', '-', $status),
            'price_cents' => 2900, 'current_version' => '1.0.0', 'status' => $status,
        ]);
    }
}
