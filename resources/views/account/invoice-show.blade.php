@extends('layouts.dashboard')
@section('title', 'Invoice INV-'.$order->id)
@section('content')
<x-page-head eyebrow="Invoices" :title="'INV-'.$order->id" :subtitle="$order->created_at->format('d M Y').' · '.str($order->status)->headline()" />
<div class="card" style="overflow:hidden">
    @foreach ($order->items as $item)
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between">
            <span>{{ $item->description }}</span><span class="mono">${{ number_format($item->unit_price_cents / 100, 2) }}</span>
        </div>
    @endforeach
    <div style="padding:14px 20px;display:flex;justify-content:space-between;font-weight:700">
        <span>Total</span><span>{{ $order->totalFormatted() }}</span>
    </div>
</div>
@endsection
