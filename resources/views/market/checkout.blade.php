@extends('layouts.app')
@section('title', 'Checkout — '.$product->title)
@section('content')
<div class="container" style="max-width:560px;padding-top:40px">
    <div class="eyebrow">Checkout</div>
    <h1 style="margin-top:8px;font-size:26px;font-weight:800;letter-spacing:-0.03em">{{ $product->title }}</h1>

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('checkout.store', $product) }}" class="card card-pad" style="display:grid;gap:18px;margin-top:20px">
        @csrf

        @if ($product->isAcquisition())
            <div class="field">
                <label>Acquisition</label>
                <div style="display:flex;justify-content:space-between;align-items:center;border:1px solid #DCDFE7;border-radius:11px;padding:12px 14px">
                    <span>One-time project acquisition</span>
                    <strong>{{ $product->askingPriceFormatted() }}</strong>
                </div>
                <input type="hidden" name="license_type" value="acquisition">
            </div>
        @else
            <div class="field">
                <label>License</label>
                <div style="display:grid;gap:8px;margin-top:4px">
                    <label style="display:flex;justify-content:space-between;align-items:center;border:1px solid #DCDFE7;border-radius:11px;padding:12px 14px;cursor:pointer">
                        <span><input type="radio" name="license_type" value="regular" checked> Regular license</span>
                        <strong>{{ $product->priceFormatted() }}</strong>
                    </label>
                    @if ($product->extended_price_cents)
                        <label style="display:flex;justify-content:space-between;align-items:center;border:1px solid #DCDFE7;border-radius:11px;padding:12px 14px;cursor:pointer">
                            <span><input type="radio" name="license_type" value="extended"> Extended license</span>
                            <strong>${{ number_format($product->extended_price_cents / 100, 0) }}</strong>
                        </label>
                    @endif
                </div>
            </div>
        @endif

        <div class="field">
            <label>Pay with</label>
            <div style="display:grid;gap:8px;margin-top:4px">
                <label style="display:flex;align-items:center;gap:10px;border:1px solid #DCDFE7;border-radius:11px;padding:12px 14px;cursor:pointer">
                    <input type="radio" name="gateway" value="stripe" checked> Card via Stripe
                </label>
                <label style="display:flex;align-items:center;gap:10px;border:1px solid #DCDFE7;border-radius:11px;padding:12px 14px;cursor:pointer">
                    <input type="radio" name="gateway" value="paystack"> Card / bank / USSD via Paystack
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-dark btn-block" style="height:48px">Continue to payment →</button>
    </form>
</div>
<div style="height:60px"></div>
@endsection
