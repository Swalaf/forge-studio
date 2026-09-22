@extends('layouts.dashboard')
@section('title', 'Your library')
@section('content')
<x-page-head eyebrow="Your account" title="Your library" subtitle="Purchases, licenses, downloads and the studio work you have commissioned.">
    <x-slot:actions>
        <a href="{{ route('market.browse') }}" class="btn btn-dark">Browse marketplace</a>
    </x-slot:actions>
</x-page-head>
<x-stat-grid :stats="$stats" />

<div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-soft);font-size:15.5px;font-weight:720">Recently licensed</div>
    @forelse ($recentLicenses as $l)
        <div style="padding:13px 20px;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div>
                <div style="font-size:13.5px;font-weight:650">{{ $l->product->title }}</div>
                <div class="mono" style="font-size:10px;color:var(--muted-2);margin-top:3px">v{{ $l->product->current_version }} · {{ $l->status }}</div>
            </div>
            <a href="{{ route('account.downloads.index') }}" class="btn btn-outline btn-sm">Downloads</a>
        </div>
    @empty
        <p style="padding:16px 20px;color:var(--muted)">Nothing purchased yet — <a href="{{ route('market.browse') }}">browse the marketplace</a>.</p>
    @endforelse
</div>
@endsection
