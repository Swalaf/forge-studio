<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'live';
        $query = Product::where('author_id', Auth::id());
        match ($tab) {
            'draft' => $query->where('status', 'draft'),
            'in_review' => $query->whereIn('status', ['in_review', 'changes_requested']),
            'unpublished' => $query->whereIn('status', ['hidden', 'rejected']),
            default => $query->where('status', 'live'),
        };
        $products = $query->latest()->paginate(10)->withQueryString();

        $rows = $products->getCollection()->map(fn ($p) => [
            'title' => $p->title, 'meta' => $p->current_version.' · v'.$p->current_version,
            'b' => $p->priceFormatted(), 'c' => (string) $p->sales_count,
            'status' => str($p->status)->headline(),
            'tone' => match ($p->status) { 'live' => 'ok', 'draft' => 'info', 'in_review', 'changes_requested' => 'wait', default => 'bad' },
            'primary' => ['label' => 'Edit', 'url' => route('author.products.edit', $p)],
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('products'),
            'title' => 'Your products', 'subtitle' => 'Live listings, drafts and the versions you have shipped.',
            'stats' => [
                ['k' => 'Live', 'v' => (string) Product::where('author_id', Auth::id())->where('status', 'live')->count(), 'tone' => 'ok'],
                ['k' => 'Drafts', 'v' => (string) Product::where('author_id', Auth::id())->where('status', 'draft')->count()],
                ['k' => 'Total sales', 'v' => (string) Product::where('author_id', Auth::id())->sum('sales_count')],
            ],
            'tabs' => collect(['live' => 'Live', 'draft' => 'Drafts', 'in_review' => 'In review', 'unpublished' => 'Unpublished'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Product', 'colB' => 'Price', 'colC' => 'Sales (30d)',
            'rows' => $rows, 'pagination' => $products->links(),
            'actionsHtml' => '<a class="btn btn-dark" href="'.route('author.products.create').'">New listing</a>',
        ]);
    }

    public function create(): View
    {
        return view('author.product-form', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('products'),
            'product' => new Product(), 'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $data['slug'] = Str::slug($data['title']).'-'.Str::random(5);
        $data['status'] = 'draft';

        $product = Product::create($data);
        AuditLog::record('product.created', $product);

        return redirect()->route('author.products.edit', $product)->with('status', 'Draft created. Submit a version when you\'re ready for review.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('author.product-form', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Author workspace', 'navGroups' => Nav::author('products'),
            'product' => $product, 'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $product->update($this->validated($request));
        AuditLog::record('product.updated', $product);

        return redirect()->route('author.products.index')->with('status', 'Product updated.');
    }

    public function submitVersion(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $data = $request->validate([
            'version' => ['required', 'string', 'max:20'],
            'type' => ['required', 'in:new,minor,patch'],
            'changelog' => ['nullable', 'string'],
        ]);

        $product->versions()->create($data + ['status' => 'pending', 'submitted_at' => now()]);
        $product->update(['status' => 'in_review']);
        AuditLog::record('product_version.submitted', $product, $data);

        return redirect()->route('author.submissions.index')->with('status', 'Submitted for review.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'extended_price_cents' => ['nullable', 'integer', 'min:0'],
            'demo_url' => ['nullable', 'url', 'max:255'],
        ]);
    }
}
