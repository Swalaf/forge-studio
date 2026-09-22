@extends('layouts.app')
@section('title', 'Forge Market — production software you can ship this week')
@section('content')

<section class="hero">
    <div class="container" style="padding-top:clamp(44px,7vw,84px);padding-bottom:clamp(40px,6vw,72px)">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:56px;align-items:center">
            <div>
                <div class="eyebrow" style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;border:1px solid rgba(255,255,255,0.18);background:rgba(255,255,255,0.06);color:#C9CEDB">
                    Built &amp; vetted by Forge Studio
                </div>
                <h1>Production software you can ship this week.</h1>
                <p>Scripts, SaaS starters, apps and plugins — built in-house, plus hand-approved work from independent developers. Every listing reviewed before it goes live.</p>

                <form action="{{ route('market.browse') }}" class="hero-search">
                    <input type="text" name="q" placeholder="Search software, scripts, SaaS &amp; solutions…">
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
            </div>
            <div style="display:grid;gap:14px">
                <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);border-radius:20px;padding:22px;display:grid;grid-template-columns:repeat(3,1fr);gap:18px">
                    <div><div style="font-size:26px;font-weight:800">{{ $totalLive }}</div><div class="eyebrow">Products</div></div>
                    <div><div style="font-size:26px;font-weight:800">{{ number_format(\App\Models\License::count()) }}</div><div class="eyebrow">Licenses sold</div></div>
                    <div><div style="font-size:26px;font-weight:800">{{ number_format(\App\Models\Product::avg('rating_avg') ?? 0, 1) }}★</div><div class="eyebrow">Avg rating</div></div>
                </div>
                <div style="background:#fff;border-radius:20px;padding:20px;color:#0B0F19">
                    <div class="eyebrow eyebrow-accent">Studio service</div>
                    <div style="font-size:19px;font-weight:750;margin-top:8px;line-height:1.3">Buy the product, let us make it yours.</div>
                    <p style="margin:10px 0 16px;font-size:13.5px;line-height:1.6;color:var(--muted)">Customization, white-labeling, installation and deployment — handled by the team that wrote the code.</p>
                    <a href="{{ route('hire-us') }}" class="btn btn-dark btn-block">Get a quote in 24h →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container" style="padding-top:clamp(34px,5vw,56px)">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:20px;flex-wrap:wrap">
        <div>
            <div class="eyebrow eyebrow-accent">Featured</div>
            <h2 style="margin-top:8px;font-size:clamp(22px,2.8vw,28px);letter-spacing:-0.03em;font-weight:750">Studio originals</h2>
        </div>
        <a href="{{ route('market.browse') }}" class="btn btn-outline">Browse all {{ $totalLive }}</a>
    </div>
    <div class="grid-cards">
        @forelse ($featured as $product)
            <x-product-card :product="$product" />
        @empty
            <p style="color:var(--muted)">No featured products yet — check back soon.</p>
        @endforelse
    </div>
</div>

<div class="container" style="padding-top:clamp(32px,5vw,52px)">
    <h2 style="margin-bottom:18px;font-size:22px;letter-spacing:-0.025em;font-weight:750">Browse by category</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px">
        @foreach ($categories as $category)
            <a href="{{ route('market.browse', ['category' => $category->slug]) }}" class="card" style="text-align:left;padding:16px;display:block;color:inherit">
                <div style="width:30px;height:30px;border-radius:9px;background:var(--border-soft);display:flex;align-items:center;justify-content:center;font-size:14px">{{ $category->mark }}</div>
                <div style="font-size:14.5px;font-weight:700;margin-top:12px;letter-spacing:-0.01em">{{ $category->name }}</div>
                <div class="eyebrow" style="margin-top:3px">{{ $category->products_count }} items</div>
            </a>
        @endforeach
    </div>
</div>

<div class="container" style="padding-top:clamp(32px,5vw,52px);display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:40px">
    <div>
        <h2 style="margin-bottom:16px;font-size:22px;letter-spacing:-0.025em;font-weight:750">Popular this month</h2>
        <div class="card" style="overflow:hidden">
            @forelse ($popular as $product)
                <a href="{{ route('market.show', $product) }}" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border-bottom:1px solid var(--border-soft);color:inherit">
                    <div class="dt-thumb" style="width:56px;height:44px"></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14.5px;font-weight:650;letter-spacing:-0.01em">{{ $product->title }}</div>
                        <div class="eyebrow" style="margin-top:3px">{{ $product->category?->name }} · {{ $product->sales_count }} sales</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:14.5px;font-weight:750">{{ $product->priceFormatted() }}</div>
                        <div style="font-size:11.5px;color:var(--muted)">★ {{ number_format($product->rating_avg, 1) }}</div>
                    </div>
                </a>
            @empty
                <p style="padding:16px;color:var(--muted)">Nothing sold yet.</p>
            @endforelse
        </div>
    </div>
    <div>
        <h2 style="margin-bottom:16px;font-size:22px;letter-spacing:-0.025em;font-weight:750">New releases</h2>
        <div style="display:grid;gap:12px">
            @forelse ($releases as $product)
                <a href="{{ route('market.show', $product) }}" class="card" style="padding:16px;display:flex;gap:14px;align-items:center;color:inherit">
                    <div class="dt-thumb" style="width:70px;height:56px"></div>
                    <div style="flex:1;min-width:0">
                        <span style="font-size:14.5px;font-weight:700;letter-spacing:-0.01em">{{ $product->title }}</span>
                        <div style="font-size:13px;color:var(--muted);margin-top:5px">{{ $product->tagline }}</div>
                    </div>
                    <div style="font-size:15px;font-weight:750">{{ $product->priceFormatted() }}</div>
                </a>
            @empty
                <p style="color:var(--muted)">No releases yet.</p>
            @endforelse
        </div>
    </div>
</div>

<section class="container" style="margin-top:64px">
    <div style="background:linear-gradient(120deg,#0B0F19 0%,#171E33 55%,oklch(0.36 0.13 268) 100%);border-radius:26px;padding:clamp(26px,4.4vw,56px);color:#fff;display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:48px;align-items:center">
        <div>
            <div class="eyebrow" style="color:#A8B0C4">Forge Studio · Engineering services</div>
            <h2 style="margin-top:14px;font-size:clamp(27px,3.8vw,42px);line-height:1.12;letter-spacing:-0.035em;font-weight:800;max-width:18ch">Need it customized? Hire our development team.</h2>
            <p style="margin-top:16px;font-size:16px;line-height:1.65;color:#B9C0D0;max-width:56ch">Feature builds, integrations, white-labeling and full custom products — delivered by the engineers behind the marketplace. Fixed scope, fixed price, 24-hour quotes.</p>
            <a href="{{ route('hire-us') }}" class="btn btn-dark" style="height:50px;padding:0 26px;border-radius:13px;background:#fff;color:#0B0F19;margin-top:28px;display:inline-flex">Start your project →</a>
        </div>
    </div>
</section>

<div style="height:60px"></div>
@endsection
