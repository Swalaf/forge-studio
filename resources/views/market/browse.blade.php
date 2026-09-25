@extends('layouts.app')
@section('title', 'Browse the marketplace')
@section('content')
<div class="container" style="padding-top:clamp(16px,3.5vw,32px)">
    <div class="eyebrow">Marketplace <span>/</span> All products</div>
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:10px 0 22px;flex-wrap:wrap">
        <h1 style="margin:0;font-size:clamp(24px,3.2vw,32px);letter-spacing:-0.035em;font-weight:800">{{ $products->total() }} products</h1>
        <form style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;flex:1 1 320px">
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <div style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #DCDFE7;border-radius:11px;height:42px;padding:0 14px;flex:1 1 200px">
                <span>⌕</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search software, scripts, SaaS…" style="border:none;outline:none;flex:1;background:transparent">
            </div>
            <select name="sort" onchange="this.form.submit()" style="height:42px;border:1px solid #DCDFE7;border-radius:11px;background:#fff;padding:0 12px">
                <option value="best_selling" @selected($sort === 'best_selling')>Sort: Best selling</option>
                <option value="newest" @selected($sort === 'newest')>Newest</option>
                <option value="price_low" @selected($sort === 'price_low')>Price: low to high</option>
                <option value="top_rated" @selected($sort === 'top_rated')>Top rated</option>
            </select>
            <button type="submit" class="btn btn-outline">Apply</button>
        </form>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:26px;align-items:flex-start">
        <aside class="card" style="padding:20px;flex:1 1 240px;max-width:300px">
            <div class="eyebrow" style="margin-bottom:11px">Categories</div>
            <div style="display:grid;gap:9px">
                <a href="{{ route('market.browse') }}" style="font-size:13.5px;color:{{ request('category') ? 'var(--muted)' : 'var(--ink)' }};font-weight:{{ request('category') ? '500' : '700' }}">All categories</a>
                @foreach ($categories as $category)
                    <a href="{{ route('market.browse', ['category' => $category->slug]) }}" style="font-size:13.5px;color:{{ request('category') === $category->slug ? 'var(--ink)' : 'var(--muted)' }};font-weight:{{ request('category') === $category->slug ? '700' : '500' }};display:flex;justify-content:space-between">
                        <span>{{ $category->name }}</span>
                        <span class="mono" style="font-size:11px;color:var(--muted-2)">{{ $category->products_count }}</span>
                    </a>
                @endforeach
            </div>
        </aside>

        <div style="flex:4 1 480px;min-width:0">
            @if ($view === 'grid')
                <div class="grid-cards">
                    @forelse ($products as $product)
                        <x-product-card :product="$product" />
                    @empty
                        <p style="color:var(--muted)">No products match those filters yet.</p>
                    @endforelse
                </div>
            @else
                <div style="display:grid;gap:12px">
                    @foreach ($products as $product)
                        <a href="{{ route('market.show', $product) }}" class="card" style="padding:14px;display:flex;gap:18px;align-items:center;color:inherit">
                            <div class="dt-thumb" style="width:150px;height:96px;border-radius:12px"></div>
                            <div style="flex:1;min-width:0">
                                <div class="eyebrow">{{ $product->category?->name }}</div>
                                <div style="font-size:17px;font-weight:700;letter-spacing:-0.02em;margin-top:6px">{{ $product->title }}</div>
                                <p style="margin-top:7px;font-size:13.5px;color:var(--muted)">{{ $product->tagline }}</p>
                            </div>
                            <div style="text-align:right;flex-shrink:0">
                                <div style="font-size:20px;font-weight:800">{{ $product->priceFormatted() }}</div>
                                <div style="font-size:12.5px;color:var(--muted)">★ {{ number_format($product->rating_avg, 1) }} · {{ $product->sales_count }} sales</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            <div style="margin-top:22px">{!! $products->links() !!}</div>

            <div style="margin-top:22px;background:#0B0F19;border-radius:18px;padding:24px 28px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;color:#fff">
                <div style="flex:1;min-width:260px">
                    <div style="font-size:18px;font-weight:750">Can't find the exact fit?</div>
                    <div style="font-size:13.5px;color:#B9C0D0;margin-top:5px">Tell us what you need — we'll customize a listing or build it from scratch.</div>
                </div>
                <a href="{{ route('hire-us') }}" class="btn btn-dark" style="background:#fff;color:#0B0F19">Hire the studio →</a>
            </div>
        </div>
    </div>
</div>
<div style="height:60px"></div>
@endsection
