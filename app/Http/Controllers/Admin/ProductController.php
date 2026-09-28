<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'all';
        $query = Product::with('author', 'category');
        match ($tab) {
            'studio' => $query->where('is_studio_original', true),
            'third_party' => $query->where('is_studio_original', false),
            'draft' => $query->where('status', 'draft'),
            'unpublished' => $query->whereIn('status', ['hidden', 'rejected']),
            default => null,
        };
        $products = $query->latest()->paginate(10)->withQueryString();

        $rows = $products->getCollection()->map(fn ($p) => [
            'title' => $p->title,
            'meta' => ($p->is_studio_original ? 'Studio original' : $p->author->name),
            'b' => $p->priceFormatted(),
            'c' => (string) $p->sales_count,
            'status' => str($p->status)->headline(),
            'tone' => match ($p->status) {
                'live' => 'ok', 'draft', 'scheduled' => 'info', 'changes_requested', 'in_review' => 'wait', 'rejected', 'hidden' => 'bad', default => 'info'
            },
            'primary' => ['label' => 'Edit', 'url' => route('admin.products.edit', $p)],
            'secondary' => $p->status === 'live'
                ? ['label' => 'Hide', 'url' => route('admin.products.hide', $p), 'method' => 'POST']
                : ['label' => 'Publish', 'url' => route('admin.products.publish', $p), 'method' => 'POST'],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('products'),
            'crumb' => 'Marketplace', 'title' => 'Products', 'subtitle' => 'Every listing, its pricing, visibility and support state.',
            'stats' => [
                ['k' => 'Live', 'v' => (string) Product::live()->count(), 'tone' => 'ok'],
                ['k' => 'Studio originals', 'v' => (string) Product::where('is_studio_original', true)->count()],
                ['k' => 'Unpublished', 'v' => (string) Product::whereIn('status', ['hidden', 'rejected'])->count()],
                ['k' => 'Avg rating', 'v' => number_format(Product::avg('rating_avg') ?? 0, 1), 'tone' => 'ok'],
            ],
            'tabs' => collect(['all' => 'All', 'studio' => 'Studio originals', 'third_party' => 'Third-party', 'draft' => 'Drafts', 'unpublished' => 'Unpublished'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Product', 'colB' => 'Price', 'colC' => 'Sales (30d)',
            'rows' => $rows, 'pagination' => $products->links(),
            'actionsHtml' => '<a class="btn btn-dark" href="'.route('admin.products.create').'">New studio product</a>',
        ]);
    }

    public function create(): View
    {
        return view('admin.product-edit', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('products'),
            'product' => new Product, 'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'extended_price_cents' => ['nullable', 'integer', 'min:0'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'release_zip' => ['nullable', 'file', 'mimes:zip', 'max:102400'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=225,max_width=6000,max_height=6000'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
        unset($data['release_zip'], $data['banner_image']);
        $data['is_featured'] = $request->boolean('is_featured');

        $product = DB::transaction(function () use ($request, $data): Product {
            $product = Product::create($data + [
                'author_id' => $request->user()->id,
                'slug' => Str::slug($data['title']).'-'.Str::random(5),
                'current_version' => '1.0.0',
                'status' => 'draft',
                'is_studio_original' => true,
            ]);

            $this->storeRelease($request, $product);
            if ($request->hasFile('banner_image')) {
                $product->replaceBanner($request->file('banner_image'));
            }
            AuditLog::record('product.created', $product);

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('status', 'Studio product draft created. Publish it when the release ZIP is ready.');
    }

    public function edit(Product $product): View
    {
        return view('admin.product-edit', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('products'),
            'product' => $product, 'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'extended_price_cents' => ['nullable', 'integer', 'min:0'],
            'demo_url' => ['nullable', 'url', 'max:255'],
            'release_zip' => ['nullable', 'file', 'mimes:zip', 'max:102400'],
            'banner_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=400,min_height=225,max_width=6000,max_height=6000'],
            'status' => ['required', 'in:draft,in_review,changes_requested,rejected,scheduled,live,hidden'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
        unset($data['release_zip'], $data['banner_image']);

        if ($request->hasFile('release_zip') && ! $product->is_studio_original) {
            return back()->withErrors(['release_zip' => 'Private ZIP uploads are only available for studio products.']);
        }

        if ($request->hasFile('banner_image') && ! $product->is_studio_original) {
            return back()->withErrors(['banner_image' => 'Image uploads for third-party products belong to their authors.']);
        }

        if ($product->is_studio_original && $data['status'] === 'live'
            && ! $request->hasFile('release_zip') && ! Storage::disk('local')->exists($product->downloadPath())) {
            return back()->withErrors(['release_zip' => 'Upload the private ZIP before publishing this studio product.']);
        }

        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['status'] === 'live') {
            $data['published_at'] = $product->published_at ?? now();
        }

        $previousBanner = DB::transaction(function () use ($request, $product, $data): ?string {
            $product->update($data);
            $this->storeRelease($request, $product);
            $previousBanner = $request->hasFile('banner_image')
                ? $product->replaceBanner($request->file('banner_image')) : null;
            AuditLog::record('product.updated', $product);

            return $previousBanner;
        });

        if ($previousBanner) {
            Storage::disk('public')->delete($previousBanner);
        }

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function publish(Product $product): RedirectResponse
    {
        if ($product->is_studio_original && ! Storage::disk('local')->exists($product->downloadPath())) {
            return back()->withErrors(['release_zip' => 'Upload the private ZIP before publishing this studio product.']);
        }

        $product->update(['status' => 'live', 'published_at' => $product->published_at ?? now()]);
        AuditLog::record('product.published', $product);

        return back()->with('status', $product->title.' is live.');
    }

    public function hide(Product $product): RedirectResponse
    {
        $product->update(['status' => 'hidden']);
        AuditLog::record('product.hidden', $product);

        return back()->with('status', $product->title.' is hidden.');
    }

    private function storeRelease(Request $request, Product $product): void
    {
        if (! $request->hasFile('release_zip')) {
            return;
        }

        $path = $request->file('release_zip')->storeAs(
            'products/'.$product->id, basename($product->downloadPath()), 'local'
        );

        if ($path === false) {
            throw new RuntimeException('Unable to store the private product ZIP.');
        }
    }
}
