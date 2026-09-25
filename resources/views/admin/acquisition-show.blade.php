@extends('layouts.dashboard')
@section('title', 'Project sale — '.$listing->title)
@section('content')
<x-page-head eyebrow="Project sales" :title="$listing->title" subtitle="Moderate visibility, offers, bids, buyer assignment and transfer status." />

<div style="display:grid;grid-template-columns:minmax(0,1.4fr) minmax(280px,.8fr);gap:18px;align-items:start">
    <div class="card card-pad">
        <div class="eyebrow">Listing details</div>
        <h2 style="font-size:22px;margin:10px 0">{{ $listing->title }}</h2>
        <p style="color:var(--muted);line-height:1.65">{{ $listing->description }}</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-top:14px">
            <div><div class="mono" style="font-size:10px;color:var(--muted-2)">ASKING</div><strong>{{ $listing->askingPriceFormatted() }}</strong></div>
            <div><div class="mono" style="font-size:10px;color:var(--muted-2)">SELLER</div><strong>{{ $listing->author->name }}</strong></div>
            <div><div class="mono" style="font-size:10px;color:var(--muted-2)">BUYER</div><strong>{{ $listing->soldTo?->name ?? '—' }}</strong></div>
            <div><div class="mono" style="font-size:10px;color:var(--muted-2)">STATUS</div><strong>{{ str($listing->acquisition_status)->headline() }}</strong></div>
        </div>
    </div>

    <aside class="card card-pad" style="display:grid;gap:10px">
        <form method="POST" action="{{ route('admin.acquisitions.approve', $listing) }}">@csrf<button class="btn btn-dark btn-block">Approve / publish</button></form>
        <form method="POST" action="{{ route('admin.acquisitions.hide', $listing) }}">@csrf<button class="btn btn-outline btn-block">Hide listing</button></form>
        @if ($listing->acquisition_sale_type === 'auction')
            <form method="POST" action="{{ route('admin.acquisitions.close-auction', $listing) }}">@csrf<button class="btn btn-outline btn-block">Close auction to highest bidder</button></form>
        @endif
        <form method="POST" action="{{ route('admin.acquisitions.transfer', $listing) }}" style="display:grid;gap:8px">
            @csrf
            <div class="field"><label>Transfer status</label><select name="acquisition_status"><option value="available" @selected($listing->acquisition_status === 'available')>Available</option><option value="pending_transfer" @selected($listing->acquisition_status === 'pending_transfer')>Pending transfer</option><option value="sold" @selected($listing->acquisition_status === 'sold')>Sold</option><option value="withdrawn" @selected($listing->acquisition_status === 'withdrawn')>Withdrawn</option></select></div>
            <button class="btn btn-dark btn-block">Update transfer</button>
        </form>
    </aside>
</div>

<div class="card card-pad" style="margin-top:18px">
    <h2 style="font-size:18px;margin-bottom:12px">Offers</h2>
    <div style="display:grid;gap:10px">
        @forelse ($listing->acquisitionOffers as $offer)
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-soft);padding-bottom:8px;gap:12px"><span>{{ $offer->buyer->name }} · {{ $offer->message }}</span><strong>{{ $offer->amountFormatted() }} · {{ str($offer->status)->headline() }}</strong></div>
        @empty
            <p style="color:var(--muted)">No offers yet.</p>
        @endforelse
    </div>
</div>

<div class="card card-pad" style="margin-top:18px">
    <h2 style="font-size:18px;margin-bottom:12px">Bids</h2>
    <div style="display:grid;gap:10px">
        @forelse ($listing->acquisitionBids as $bid)
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border-soft);padding-bottom:8px"><span>{{ $bid->bidder->name }}</span><strong>{{ $bid->amountFormatted() }} · {{ str($bid->status)->headline() }}</strong></div>
        @empty
            <p style="color:var(--muted)">No bids yet.</p>
        @endforelse
    </div>
</div>
@endsection
