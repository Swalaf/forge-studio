@extends('layouts.app')
@section('title', $product->title)
@section('content')
<div class="container" style="padding-top:22px">
    <div class="eyebrow">Marketplace / {{ $product->category?->name }} / {{ $product->title }}</div>
</div>
<div class="container" style="padding-top:14px;display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
    <div style="flex:3 1 460px;min-width:0">
        <div class="card" style="overflow:hidden">
            <div style="height:clamp(200px,32vw,420px);background:repeating-linear-gradient(135deg,#EDEFF4 0 14px,#E4E7EF 14px 28px)"></div>
        </div>

        <div style="padding:26px 0 0">
            <h2 style="font-size:22px;letter-spacing:-0.025em;font-weight:750;margin-bottom:12px">{{ $product->tagline }}</h2>
            <p style="font-size:15px;line-height:1.7;color:#4A5262;max-width:75ch;margin-bottom:24px">{{ $product->description }}</p>

            @if (!empty($product->tech_stack))
                <div class="card card-pad" style="margin-bottom:16px">
                    <div class="eyebrow">Technologies</div>
                    <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:12px">
                        @foreach ($product->tech_stack as $tech)
                            <span class="mono" style="font-size:11.5px;background:var(--border-soft);border-radius:7px;padding:6px 10px;color:#39404F">{{ $tech }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($product->requirements))
                <div class="card card-pad" style="margin-bottom:24px">
                    <div class="eyebrow">Requirements</div>
                    <div style="display:grid;gap:8px;margin-top:12px">
                        @foreach ($product->requirements as $k => $v)
                            <div style="display:flex;justify-content:space-between;font-size:13.5px;border-bottom:1px solid var(--border-soft);padding-bottom:7px">
                                <span style="color:var(--muted)">{{ $k }}</span><span style="font-weight:650">{{ $v }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card card-pad" style="margin-bottom:14px">
                <div style="display:flex;gap:28px;align-items:center;margin-bottom:14px">
                    <div style="text-align:center;padding-right:28px;border-right:1px solid var(--border-soft)">
                        <div style="font-size:42px;font-weight:800;letter-spacing:-0.03em">{{ number_format($product->rating_avg, 1) }}</div>
                        <div class="mono" style="font-size:11px;color:var(--muted-2);margin-top:4px">{{ $product->rating_count }} reviews</div>
                    </div>
                    <div style="flex:1;display:grid;gap:7px">
                        @foreach ($ratingBars as $b)
                            <div style="display:flex;align-items:center;gap:12px">
                                <span class="mono" style="font-size:11.5px;color:var(--muted);width:14px">{{ $b['stars'] }}</span>
                                <div style="flex:1;height:7px;border-radius:99px;background:var(--border-soft);overflow:hidden"><div style="width:{{ $b['pct'] }}%;height:100%;background:#0B0F19"></div></div>
                                <span class="mono" style="font-size:11.5px;color:var(--muted-2);width:38px;text-align:right">{{ $b['pct'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @auth
                    @if (auth()->user()->isCustomer())
                        <form method="POST" action="{{ route('reviews.store', $product) }}" style="border-top:1px solid var(--border-soft);padding-top:14px;display:grid;gap:10px">
                            @csrf
                            <div class="field">
                                <label>Your rating</label>
                                <select name="rating" required>
                                    <option value="5">★★★★★ 5</option>
                                    <option value="4">★★★★ 4</option>
                                    <option value="3">★★★ 3</option>
                                    <option value="2">★★ 2</option>
                                    <option value="1">★ 1</option>
                                </select>
                            </div>
                            <div class="field"><label>Review</label><textarea name="body" rows="3" required></textarea></div>
                            <button type="submit" class="btn btn-dark" style="justify-self:start">Post review</button>
                        </form>
                    @endif
                @endauth
            </div>

            <div style="display:grid;gap:12px">
                @forelse ($reviews as $review)
                    <div class="card card-pad">
                        <div style="display:flex;gap:12px;align-items:center">
                            <div class="dt-avatar">{{ $review->customer->initials() }}</div>
                            <div style="flex:1">
                                <div style="font-size:14px;font-weight:650">{{ $review->customer->name }}</div>
                                <div class="mono" style="font-size:11px;color:var(--muted-2);margin-top:2px">{{ $review->created_at->format('d M Y') }}</div>
                            </div>
                            <div style="font-size:13px;font-weight:700">★ {{ $review->rating }}</div>
                        </div>
                        <p style="margin-top:12px;font-size:13.5px;line-height:1.65;color:#4A5262">{{ $review->body }}</p>
                        @if ($review->reply_body)
                            <div style="margin-top:12px;padding:12px;background:var(--border-soft);border-radius:12px;font-size:13px;color:#4A5262">
                                <strong>Author reply:</strong> {{ $review->reply_body }}
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="color:var(--muted)">No reviews yet — be the first.</p>
                @endforelse
            </div>
        </div>
    </div>

    <aside style="display:grid;gap:14px;flex:1 1 320px;min-width:0">
        <div class="card card-pad">
            @if ($product->is_studio_original)
                <span class="pill" style="background:#0B0F19;color:#fff">Studio original</span>
            @endif
            <h1 style="margin:14px 0 0;font-size:clamp(21px,2.6vw,26px);line-height:1.2;letter-spacing:-0.03em;font-weight:800">{{ $product->title }}</h1>
            <div style="display:flex;align-items:center;gap:12px;margin-top:10px;font-size:13px;color:var(--muted)">
                <span style="color:var(--ink);font-weight:700">★ {{ number_format($product->rating_avg, 1) }}</span>
                <span>{{ $product->rating_count }} reviews</span> · <span>{{ $product->sales_count }} sales</span>
            </div>
            <div style="display:flex;align-items:baseline;gap:10px;margin:20px 0 4px">
                <span style="font-size:38px;font-weight:800;letter-spacing:-0.035em">{{ $product->priceFormatted() }}</span>
                @if ($product->compare_at_price_cents)
                    <span style="font-size:15px;color:var(--muted-2);text-decoration:line-through">${{ number_format($product->compare_at_price_cents / 100, 0) }}</span>
                @endif
            </div>
            <div style="font-size:12.5px;color:var(--muted)">Regular license · 6 months support · lifetime updates</div>
            <a href="{{ route('checkout.create', $product) }}" class="btn btn-dark btn-block" style="height:50px;margin-top:18px">Buy now — {{ $product->priceFormatted() }}</a>
            @if ($product->demo_url)
                <a href="{{ $product->demo_url }}" class="btn btn-outline btn-block" style="height:46px;margin-top:10px">Live demo ↗</a>
            @endif
        </div>
        <div class="card card-pad">
            <div style="display:flex;gap:12px;align-items:center">
                <div class="dash-logo" style="background:#0B0F19;color:#fff">{{ substr($product->author->name, 0, 1) }}</div>
                <div style="flex:1">
                    <div style="font-size:15px;font-weight:700">{{ $product->author->name }}</div>
                    <div class="mono" style="font-size:10.5px;color:var(--muted);margin-top:2px">{{ $product->author->products()->count() }} products</div>
                </div>
            </div>
        </div>
    </aside>
</div>
<div style="height:60px"></div>
@endsection
