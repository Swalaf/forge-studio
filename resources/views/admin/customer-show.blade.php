@extends('layouts.dashboard')
@section('title', $customer->name)
@section('content')
<x-page-head eyebrow="Marketplace" :title="$customer->company ?: $customer->name" :subtitle="$customer->email" />

<div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-soft);font-weight:720">Orders</div>
    @forelse ($orders as $o)
        <div style="padding:13px 20px;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between">
            <span>{{ $o->order_number }}</span><span class="mono" style="color:var(--muted)">{{ $o->totalFormatted() }}</span>
        </div>
    @empty
        <p style="padding:16px 20px;color:var(--muted)">No orders yet.</p>
    @endforelse
</div>
@endsection
