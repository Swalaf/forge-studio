@extends('layouts.dashboard')
@section('title', 'Saved items')
@section('content')
<x-page-head eyebrow="Your account" title="Saved items" subtitle="Products you bookmarked while browsing." />
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">
    @forelse ($saved as $item)
        <div class="card" style="overflow:hidden">
            <a href="{{ route('market.show', $item->product) }}" style="display:block;color:inherit">
                <div class="product-shot" style="height:130px"></div>
                <div style="padding:14px">
                    <div style="font-size:14.5px;font-weight:700">{{ $item->product->title }}</div>
                    <div class="eyebrow" style="margin-top:4px">{{ $item->product->category?->name }}</div>
                </div>
            </a>
            <div style="padding:0 14px 14px;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:16px;font-weight:800">{{ $item->product->priceFormatted() }}</span>
                <form method="POST" action="{{ route('account.saved.destroy', $item->product) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline btn-sm">Remove</button>
                </form>
            </div>
        </div>
    @empty
        <p style="color:var(--muted)">Nothing saved yet — bookmark a product from its page.</p>
    @endforelse
</div>
@endsection
