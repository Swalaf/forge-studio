<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        ]);
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
            'status' => ['required', 'in:draft,in_review,changes_requested,rejected,scheduled,live,hidden'],
            'is_featured' => ['sometimes', 'boolean'],
        ]);
        $data['is_featured'] = $request->boolean('is_featured');

        $product->update($data);
        AuditLog::record('product.updated', $product);

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function publish(Product $product): RedirectResponse
    {
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
}
