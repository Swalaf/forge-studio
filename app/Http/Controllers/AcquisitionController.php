<?php

namespace App\Http\Controllers;

use App\Models\AcquisitionBid;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcquisitionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::availableAcquisition()->with(['author', 'category']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($builder) => $builder
                ->where('title', 'like', "%{$search}%")
                ->orWhere('tagline', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($saleType = $request->string('sale_type')->value()) {
            $query->where('acquisition_sale_type', $saleType);
        }

        if ($request->filled('min_price')) {
            $query->where('asking_price_cents', '>=', (int) $request->integer('min_price') * 100);
        }

        if ($request->filled('max_price')) {
            $query->where('asking_price_cents', '<=', (int) $request->integer('max_price') * 100);
        }

        if ($request->filled('min_revenue')) {
            $query->where('monthly_revenue_cents', '>=', (int) $request->integer('min_revenue') * 100);
        }

        if ($categorySlug = $request->string('category')->value()) {
            $query->whereHas('category', fn ($builder) => $builder->where('slug', $categorySlug));
        }

        $sort = $request->string('sort')->value() ?: 'newest';
        match ($sort) {
            'price_low' => $query->orderBy('asking_price_cents'),
            'revenue' => $query->orderByDesc('monthly_revenue_cents'),
            'ending_soon' => $query->where('acquisition_sale_type', 'auction')->orderBy('auction_ends_at'),
            default => $query->latest('published_at'),
        };

        return view('acquisitions.index', [
            'listings' => $query->paginate(12)->withQueryString(),
            'categories' => Category::withCount(['products' => fn ($builder) => $builder->availableAcquisition()])->orderBy('name')->get(),
            'sort' => $sort,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->isAcquisition() && $product->isLive(), 404);

        $product->increment('view_count');
        $product->load(['author', 'category', 'acquisitionBids.bidder', 'acquisitionOffers']);

        return view('acquisitions.show', [
            'listing' => $product,
            'highestBid' => $product->acquisitionBids()->where('status', 'active')->with('bidder')->orderByDesc('amount_cents')->first(),
            'recentOffers' => $product->acquisitionOffers()->where('status', 'pending')->take(5)->get(),
        ]);
    }

    public function offer(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isAvailableAcquisition() && $product->acceptsOffers(), 404);

        $minimum = (int) ceil(($product->minimum_offer_cents ?? 0) / 100);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:'.$minimum],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $product->acquisitionOffers()->create([
            'buyer_id' => $request->user()->id,
            'amount_cents' => $data['amount'] * 100,
            'currency' => 'usd',
            'message' => $data['message'] ?? null,
            'expires_at' => now()->addDays(7),
        ]);

        return back()->with('status', 'Offer submitted. The seller can review and respond from their dashboard.');
    }

    public function bid(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->isAvailableAcquisition() && $product->acceptsBids(), 404);

        $minimumBid = (int) ceil($product->minimumBidCents() / 100);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:'.$minimumBid],
        ]);

        AcquisitionBid::where('product_id', $product->id)->where('status', 'winning')->update(['status' => 'outbid']);

        $product->acquisitionBids()->create([
            'bidder_id' => $request->user()->id,
            'amount_cents' => $data['amount'] * 100,
            'currency' => 'usd',
            'status' => 'winning',
            'placed_at' => now(),
        ]);

        return back()->with('status', 'Bid placed. You are currently the highest bidder.');
    }
}
