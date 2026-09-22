@extends('layouts.app')
@section('title', 'Order '.$order->order_number)
@section('content')
<div class="container" style="max-width:560px;padding-top:60px;text-align:center">
    @if ($order->status === 'paid')
        <div style="width:56px;height:56px;border-radius:99px;background:var(--ok-bg);color:var(--ok-text);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto">✓</div>
        <h1 style="margin-top:20px;font-size:24px;font-weight:800">Payment received</h1>
        <p style="color:var(--muted);margin-top:8px">Order {{ $order->order_number }} — {{ $order->totalFormatted() }}</p>
        <a href="{{ route('account.purchases.index') }}" class="btn btn-dark" style="margin-top:24px">View your purchases →</a>
    @else
        <div style="width:56px;height:56px;border-radius:99px;background:var(--wait-bg);color:var(--wait-text);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto">⏳</div>
        <h1 style="margin-top:20px;font-size:24px;font-weight:800">Confirming your payment…</h1>
        <p style="color:var(--muted);margin-top:8px">This usually takes a few seconds. Refresh this page, or check "Purchases" in your account shortly.</p>
        <a href="{{ route('account.purchases.index') }}" class="btn btn-outline" style="margin-top:24px">Go to your account →</a>
    @endif
</div>
<div style="height:80px"></div>
@endsection
