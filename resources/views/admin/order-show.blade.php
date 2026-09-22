@extends('layouts.dashboard')
@section('title', $order->order_number)
@section('content')
<x-page-head eyebrow="Commerce" :title="$order->order_number" :subtitle="$order->customer->name.' · '.$order->created_at->format('d M Y')">
    <x-slot:actions>
        @if ($order->status === 'paid')
            <form method="POST" action="{{ route('admin.orders.refund', $order) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="btn btn-outline">Refund</button></form>
        @endif
    </x-slot:actions>
</x-page-head>

<div class="card" style="overflow:hidden">
    @foreach ($order->items as $item)
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between">
            <span>{{ $item->description }}</span>
            <span class="mono">${{ number_format($item->unit_price_cents / 100, 2) }}</span>
        </div>
    @endforeach
    <div style="padding:14px 20px;display:flex;justify-content:space-between;font-weight:700">
        <span>Total</span><span>{{ $order->totalFormatted() }}</span>
    </div>
</div>
@endsection
