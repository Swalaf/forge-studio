@extends('layouts.dashboard')
@section('title', $listing->exists ? 'Project sale — '.$listing->title : 'New project sale')
@section('content')
<x-page-head eyebrow="Project sales" :title="$listing->exists ? $listing->title : 'New project sale'" subtitle="List a one-time project acquisition with Flippa-style offers or auction bidding." />

<form method="POST" action="{{ $listing->exists ? route('author.acquisitions.update', $listing) : route('author.acquisitions.store') }}" class="card card-pad" style="display:grid;gap:16px;max-width:820px">
    @csrf
    @if ($listing->exists) @method('PUT') @endif
    <div class="field"><label>Title</label><input type="text" name="title" value="{{ old('title', $listing->title) }}" required></div>
    <div class="field"><label>Tagline</label><input type="text" name="tagline" value="{{ old('tagline', $listing->tagline) }}"></div>
    <div class="field"><label>Description</label><textarea name="description" rows="5">{{ old('description', $listing->description) }}</textarea></div>
    <div class="field-row">
        <div class="field">
            <label>Category</label>
            <select name="category_id">
                <option value="">—</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $listing->category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Sale type</label>
            <select name="acquisition_sale_type" required>
                @foreach (['fixed' => 'Buy now', 'offer' => 'Offers only', 'fixed_or_offer' => 'Buy now or offers', 'auction' => 'Auction'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('acquisition_sale_type', $listing->acquisition_sale_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="field-row">
        <div class="field"><label>Asking price (cents)</label><input type="number" name="asking_price_cents" value="{{ old('asking_price_cents', $listing->asking_price_cents) }}" required></div>
        <div class="field"><label>Minimum offer (cents)</label><input type="number" name="minimum_offer_cents" value="{{ old('minimum_offer_cents', $listing->minimum_offer_cents) }}"></div>
        <div class="field"><label>Reserve price (cents)</label><input type="number" name="reserve_price_cents" value="{{ old('reserve_price_cents', $listing->reserve_price_cents) }}"></div>
    </div>
    <div class="field-row">
        <div class="field"><label>Monthly revenue (cents)</label><input type="number" name="monthly_revenue_cents" value="{{ old('monthly_revenue_cents', $listing->monthly_revenue_cents) }}"></div>
        <div class="field"><label>Monthly profit (cents)</label><input type="number" name="monthly_profit_cents" value="{{ old('monthly_profit_cents', $listing->monthly_profit_cents) }}"></div>
    </div>
    <div class="field-row">
        <div class="field"><label>Monthly visitors</label><input type="number" name="monthly_visitors" value="{{ old('monthly_visitors', $listing->monthly_visitors) }}"></div>
        <div class="field"><label>Monthly pageviews</label><input type="number" name="monthly_pageviews" value="{{ old('monthly_pageviews', $listing->monthly_pageviews) }}"></div>
    </div>
    <div class="field-row">
        <div class="field"><label>Auction starts at</label><input type="datetime-local" name="auction_starts_at" value="{{ old('auction_starts_at', $listing->auction_starts_at?->format('Y-m-d\TH:i')) }}"></div>
        <div class="field"><label>Auction ends at</label><input type="datetime-local" name="auction_ends_at" value="{{ old('auction_ends_at', $listing->auction_ends_at?->format('Y-m-d\TH:i')) }}"></div>
    </div>
    <div class="field-row">
        <div class="field"><label>Demo URL</label><input type="url" name="demo_url" value="{{ old('demo_url', $listing->demo_url) }}"></div>
        <div class="field"><label>Repository URL</label><input type="url" name="repository_url" value="{{ old('repository_url', $listing->repository_url) }}"></div>
    </div>
    <div class="field"><label>Seller disclosures</label><textarea name="seller_disclosures" rows="3">{{ old('seller_disclosures', $listing->seller_disclosures) }}</textarea></div>
    <div class="field"><label>Transfer notes</label><textarea name="transfer_notes" rows="3">{{ old('transfer_notes', $listing->transfer_notes) }}</textarea></div>
    <div class="field"><label>Due diligence notes</label><textarea name="due_diligence_notes" rows="3">{{ old('due_diligence_notes', $listing->due_diligence_notes) }}</textarea></div>
    <button type="submit" class="btn btn-dark" style="justify-self:start">{{ $listing->exists ? 'Save project sale' : 'Create project sale draft' }}</button>
</form>

@if ($listing->exists)
    <div class="card card-pad" style="margin-top:18px;max-width:920px">
        <h2 style="font-size:18px;margin-bottom:12px">Offers</h2>
        <div style="display:grid;gap:10px">
            @forelse ($offers as $offer)
                <div style="border:1px solid var(--border-soft);border-radius:12px;padding:12px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap">
                    <div><strong>{{ $offer->buyer->name }}</strong><div class="mono" style="font-size:11px;color:var(--muted)">{{ $offer->amountFormatted() }} · {{ str($offer->status)->headline() }}</div><div style="font-size:13px;color:var(--muted);margin-top:4px">{{ $offer->message }}</div></div>
                    @if ($offer->status === 'pending')
                        <form method="POST" action="{{ route('author.acquisitions.offers.respond', $offer) }}" style="display:flex;gap:8px;flex-wrap:wrap">
                            @csrf
                            <input type="hidden" name="seller_response" value="Accepted from seller dashboard.">
                            <button name="decision" value="accepted" class="btn btn-dark btn-sm">Accept</button>
                            <button name="decision" value="rejected" class="btn btn-outline btn-sm">Reject</button>
                        </form>
                    @endif
                </div>
            @empty
                <p style="color:var(--muted)">No offers yet.</p>
            @endforelse
        </div>
    </div>

    <div class="card card-pad" style="margin-top:18px;max-width:920px">
        <h2 style="font-size:18px;margin-bottom:12px">Bids</h2>
        <div style="display:grid;gap:8px">
            @forelse ($bids as $bid)
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-soft);padding-bottom:8px"><span>{{ $bid->bidder->name }}</span><strong>{{ $bid->amountFormatted() }} · {{ str($bid->status)->headline() }}</strong></div>
            @empty
                <p style="color:var(--muted)">No bids yet.</p>
            @endforelse
        </div>
    </div>
@endif
@endsection
