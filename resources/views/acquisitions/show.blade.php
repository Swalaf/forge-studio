@extends('layouts.app')
@section('title', $listing->title.' acquisition')
@section('content')
<div class="container" style="padding-top:24px">
    <div class="eyebrow">Project acquisitions / {{ $listing->category?->name }} / {{ $listing->title }}</div>
</div>
<div class="container" style="padding-top:14px;display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
    <div style="flex:3 1 560px;min-width:0">
        <div class="card" style="height:clamp(220px,34vw,430px);background:linear-gradient(135deg,#0F172A,#334155);position:relative;overflow:hidden">
            <div style="position:absolute;left:24px;bottom:24px;color:#fff;max-width:620px">
                <span class="pill" style="background:#fff;color:#0B0F19">{{ str($listing->acquisition_sale_type)->headline() }}</span>
                <h1 style="font-size:clamp(28px,4vw,46px);letter-spacing:-0.05em;margin:14px 0 8px;font-weight:850">{{ $listing->title }}</h1>
                <p style="color:#CBD5E1;margin:0;line-height:1.6">{{ $listing->tagline }}</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:18px 0">
            <div class="card card-pad"><div class="eyebrow">Revenue / mo</div><strong style="font-size:24px">{{ $listing->monthlyRevenueFormatted() }}</strong></div>
            <div class="card card-pad"><div class="eyebrow">Profit / mo</div><strong style="font-size:24px">{{ $listing->monthlyProfitFormatted() }}</strong></div>
            <div class="card card-pad"><div class="eyebrow">Visitors / mo</div><strong style="font-size:24px">{{ number_format($listing->monthly_visitors ?? 0) }}</strong></div>
            <div class="card card-pad"><div class="eyebrow">Pageviews / mo</div><strong style="font-size:24px">{{ number_format($listing->monthly_pageviews ?? 0) }}</strong></div>
        </div>

        <div class="card card-pad" style="margin-bottom:16px">
            <h2 style="font-size:20px;letter-spacing:-0.025em;margin-bottom:10px">About this project</h2>
            <p style="line-height:1.75;color:#4A5262">{{ $listing->description }}</p>
        </div>

        @if (!empty($listing->transfer_assets))
            <div class="card card-pad" style="margin-bottom:16px">
                <div class="eyebrow">Included in transfer</div>
                <div style="display:grid;gap:10px;margin-top:14px">
                    @foreach ($listing->transfer_assets as $asset)
                        <div style="display:flex;gap:10px;align-items:center;color:#334155"><span>✓</span><span>{{ $asset }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($listing->verified_metrics))
            <div class="card card-pad" style="margin-bottom:16px">
                <div class="eyebrow">Verified metrics</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-top:14px">
                    @foreach ($listing->verified_metrics as $label => $value)
                        <div style="background:var(--border-soft);border-radius:12px;padding:12px"><div class="mono" style="font-size:10px;color:var(--muted-2)">{{ str($label)->headline() }}</div><strong>{{ $value }}</strong></div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card card-pad" style="margin-bottom:16px">
            <div class="eyebrow">Seller disclosures</div>
            <p style="line-height:1.7;color:#4A5262;margin-top:10px">{{ $listing->seller_disclosures ?: 'No additional disclosures provided.' }}</p>
        </div>

        <div class="card card-pad">
            <div class="eyebrow">Transfer notes</div>
            <p style="line-height:1.7;color:#4A5262;margin-top:10px">{{ $listing->transfer_notes ?: 'Transfer details will be coordinated after offer acceptance or payment.' }}</p>
        </div>
    </div>

    <aside style="display:grid;gap:14px;flex:1 1 330px;min-width:0">
        <div class="card card-pad">
            <div class="eyebrow">Asking price</div>
            <div style="font-size:42px;font-weight:850;letter-spacing:-0.045em;margin-top:8px">{{ $listing->askingPriceFormatted() }}</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:4px">One-time acquisition · source and assets transfer after settlement</div>

            @auth
                @if (auth()->user()->isCustomer())
                    @if (in_array($listing->acquisition_sale_type, ['fixed', 'fixed_or_offer'], true) && $listing->acquisition_status === 'available')
                        <a href="{{ route('checkout.create', $listing) }}" class="btn btn-dark btn-block" style="margin-top:18px">Buy now — {{ $listing->askingPriceFormatted() }}</a>
                    @endif

                    @if ($listing->acceptsOffers())
                        <form method="POST" action="{{ route('acquisitions.offers.store', $listing) }}" style="display:grid;gap:10px;margin-top:18px">
                            @csrf
                            <div class="field">
                                <label>Your offer (USD)</label>
                                <input type="number" name="amount" min="{{ (int) ceil(($listing->minimum_offer_cents ?? 0) / 100) }}" required>
                            </div>
                            <div class="field">
                                <label>Message to seller</label>
                                <textarea name="message" rows="3" placeholder="Timeline, proof of funds, transfer expectations…"></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark btn-block">Make offer</button>
                        </form>
                    @endif

                    @if ($listing->acquisition_sale_type === 'auction')
                        <div style="border-top:1px solid var(--border-soft);margin-top:18px;padding-top:18px">
                            <div style="font-size:13px;color:var(--muted)">Highest bid</div>
                            <strong style="font-size:24px">{{ $highestBid?->amountFormatted() ?? 'No bids yet' }}</strong>
                            @if ($listing->auction_ends_at)
                                <div class="mono" style="font-size:11px;color:var(--muted-2);margin-top:4px">Ends {{ $listing->auction_ends_at->diffForHumans() }}</div>
                            @endif
                            <form method="POST" action="{{ route('acquisitions.bids.store', $listing) }}" style="display:grid;gap:10px;margin-top:12px">
                                @csrf
                                <div class="field">
                                    <label>Your bid (min ${{ number_format((int) ceil($listing->minimumBidCents() / 100)) }})</label>
                                    <input type="number" name="amount" min="{{ (int) ceil($listing->minimumBidCents() / 100) }}" required>
                                </div>
                                <button type="submit" class="btn btn-dark btn-block">Place bid</button>
                            </form>
                        </div>
                    @endif
                @endif
            @else
                <a href="{{ route('login') }}" class="btn btn-dark btn-block" style="margin-top:18px">Sign in to offer or bid</a>
            @endauth

            @if ($errors->any())
                <div class="alert alert-error" style="margin-top:14px">{{ $errors->first() }}</div>
            @endif
        </div>

        <div class="card card-pad">
            <div class="eyebrow">Seller</div>
            <div style="display:flex;gap:12px;align-items:center;margin-top:12px">
                <div class="dash-logo" style="background:#0B0F19;color:#fff">{{ substr($listing->author->name, 0, 1) }}</div>
                <div><strong>{{ $listing->author->name }}</strong><div class="mono" style="font-size:11px;color:var(--muted-2)">{{ $listing->author->country ?? 'Remote' }}</div></div>
            </div>
        </div>

        <div class="card card-pad">
            <div class="eyebrow">Due diligence</div>
            <p style="font-size:13.5px;line-height:1.65;color:#4A5262;margin-top:10px">{{ $listing->due_diligence_notes ?: 'Review revenue, traffic, code quality, support obligations, and transfer scope before making an offer.' }}</p>
        </div>
    </aside>
</div>
<div style="height:60px"></div>
@endsection
