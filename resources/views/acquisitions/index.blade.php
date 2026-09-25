@extends('layouts.app')
@section('title', 'Projects for sale')
@section('content')
<div class="container" style="padding-top:clamp(18px,3.5vw,36px)">
    <div class="eyebrow">Project acquisitions <span>/</span> One-time sales</div>
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin:10px 0 24px;flex-wrap:wrap">
        <div>
            <h1 style="margin:0;font-size:clamp(26px,4vw,42px);letter-spacing:-0.045em;font-weight:850">Buy full projects, not just licenses.</h1>
            <p style="margin-top:10px;max-width:720px;color:var(--muted);font-size:15px;line-height:1.65">Flippa-style listings for SaaS apps, scripts, domains, source code, customer assets, and revenue-generating projects that transfer to one buyer.</p>
        </div>
    </div>

    <form class="card card-pad" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:22px">
        <div class="field">
            <label>Search</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="SaaS, CRM, Laravel…">
        </div>
        <div class="field">
            <label>Sale type</label>
            <select name="sale_type">
                <option value="">Any</option>
                <option value="fixed" @selected(request('sale_type') === 'fixed')>Buy now</option>
                <option value="offer" @selected(request('sale_type') === 'offer')>Make offer</option>
                <option value="fixed_or_offer" @selected(request('sale_type') === 'fixed_or_offer')>Buy or offer</option>
                <option value="auction" @selected(request('sale_type') === 'auction')>Auction</option>
            </select>
        </div>
        <div class="field">
            <label>Min price</label>
            <input type="number" name="min_price" value="{{ request('min_price') }}" min="0" step="100">
        </div>
        <div class="field">
            <label>Max price</label>
            <input type="number" name="max_price" value="{{ request('max_price') }}" min="0" step="100">
        </div>
        <div class="field">
            <label>Min revenue/mo</label>
            <input type="number" name="min_revenue" value="{{ request('min_revenue') }}" min="0" step="100">
        </div>
        <div class="field">
            <label>Sort</label>
            <select name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest</option>
                <option value="price_low" @selected($sort === 'price_low')>Price low</option>
                <option value="revenue" @selected($sort === 'revenue')>Revenue</option>
                <option value="ending_soon" @selected($sort === 'ending_soon')>Ending soon</option>
            </select>
        </div>
        <button type="submit" class="btn btn-dark" style="align-self:end;height:44px">Apply filters</button>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @forelse ($listings as $listing)
            <a href="{{ route('acquisitions.show', $listing) }}" class="card" style="padding:18px;color:inherit;display:grid;gap:14px">
                <div style="height:150px;border-radius:14px;background:linear-gradient(135deg,#111827,#334155);position:relative;overflow:hidden">
                    <span class="pill" style="position:absolute;left:14px;top:14px;background:#fff;color:#0B0F19">{{ str($listing->acquisition_sale_type)->headline() }}</span>
                    @if ($listing->acquisition_sale_type === 'auction' && $listing->auction_ends_at)
                        <span class="pill" style="position:absolute;right:14px;top:14px;background:#F59E0B;color:#111827">Ends {{ $listing->auction_ends_at->diffForHumans() }}</span>
                    @endif
                </div>
                <div>
                    <div class="eyebrow">{{ $listing->category?->name ?? 'Project' }}</div>
                    <h2 style="font-size:18px;letter-spacing:-0.025em;margin:7px 0 6px;font-weight:800">{{ $listing->title }}</h2>
                    <p style="margin:0;color:var(--muted);font-size:13.5px;line-height:1.55">{{ $listing->tagline }}</p>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;border-top:1px solid var(--border-soft);padding-top:12px">
                    <div><div class="mono" style="font-size:10px;color:var(--muted-2)">ASKING</div><strong>{{ $listing->askingPriceFormatted() }}</strong></div>
                    <div><div class="mono" style="font-size:10px;color:var(--muted-2)">REV/MO</div><strong>{{ $listing->monthlyRevenueFormatted() }}</strong></div>
                    <div><div class="mono" style="font-size:10px;color:var(--muted-2)">VISITS</div><strong>{{ number_format($listing->monthly_visitors ?? 0) }}</strong></div>
                </div>
            </a>
        @empty
            <p style="color:var(--muted)">No acquisition listings match those filters yet.</p>
        @endforelse
    </div>

    <div style="margin-top:22px">{!! $listings->links() !!}</div>
</div>
<div style="height:60px"></div>
@endsection
