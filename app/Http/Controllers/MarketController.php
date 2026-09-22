<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketController extends Controller
{
    public function home(): View
    {
        $featured = Product::live()->where('is_featured', true)->with('author')->latest('published_at')->take(3)->get();
        $popular = Product::live()->with('author')->orderByDesc('sales_count')->take(5)->get();
        $releases = Product::live()->with('author')->latest('published_at')->take(4)->get();
        $categories = Category::withCount(['products' => fn ($q) => $q->live()])->orderBy('name')->get();

        return view('market.home', [
            'featured' => $featured,
            'popular' => $popular,
            'releases' => $releases,
            'categories' => $categories,
            'totalLive' => Product::live()->count(),
        ]);
    }

    public function browse(Request $request): View
    {
        $query = Product::live()->with(['author', 'category']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('tagline', 'like', "%{$search}%"));
        }

        if ($categorySlug = $request->string('category')->value()) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $sort = $request->string('sort')->value() ?: 'best_selling';
        match ($sort) {
            'newest' => $query->latest('published_at'),
            'price_low' => $query->orderBy('price_cents'),
            'top_rated' => $query->orderByDesc('rating_avg'),
            default => $query->orderByDesc('sales_count'),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::withCount(['products' => fn ($q) => $q->live()])->orderBy('name')->get();

        return view('market.browse', [
            'products' => $products,
            'categories' => $categories,
            'sort' => $sort,
            'view' => $request->string('view')->value() === 'list' ? 'list' : 'grid',
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'live', 404);

        $product->increment('view_count');
        $product->load(['author', 'category', 'media']);
        $reviews = $product->reviews()->where('status', 'published')->with('customer')->latest()->take(10)->get();

        $ratingBars = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = $product->reviews()->where('status', 'published')->where('rating', $star)->count();
            $pct = $product->rating_count ? round($count / $product->rating_count * 100) : 0;
            $ratingBars[] = ['stars' => $star, 'pct' => $pct];
        }

        return view('market.product', [
            'product' => $product,
            'reviews' => $reviews,
            'ratingBars' => $ratingBars,
            'related' => Product::live()->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)->take(4)->get(),
        ]);
    }
}
