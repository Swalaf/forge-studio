@extends('layouts.app')
@section('title', 'Hire the studio')
@section('content')
<section style="background:#0B0F19;color:#fff">
    <div class="container" style="padding:clamp(40px,6vw,72px) clamp(16px,4vw,32px) clamp(36px,5vw,64px);text-align:center">
        <div class="eyebrow" style="color:#A8B0C4">Forge Studio · Engineering services</div>
        <h1 style="margin:16px auto 0;font-size:clamp(30px,5.2vw,54px);line-height:1.08;letter-spacing:-0.04em;font-weight:800;max-width:17ch">Need customization, development or installation? Hire us.</h1>
        <p style="margin:18px auto 0;font-size:17px;line-height:1.65;color:#B9C0D0;max-width:64ch">The same engineers who build the marketplace take on client work. Scoped in 24 hours, fixed price, shipped in sprints you can watch.</p>
    </div>
</section>

<div class="container" style="max-width:720px;padding-top:40px">
    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('hire-us.store') }}" class="card card-pad" style="display:grid;gap:16px">
        @csrf
        <div class="field">
            <label>What do you need?</label>
            <select name="project_type" required>
                <option value="customization">Customize a product I own</option>
                <option value="installation">Install &amp; configure a product</option>
                <option value="custom_build">Build something custom</option>
            </select>
        </div>
        <div class="field">
            <label>Budget range</label>
            <select name="budget_range">
                <option value="">Prefer not to say</option>
                <option value="under_1k">Under $1,000</option>
                <option value="1k_5k">$1,000 – $5,000</option>
                <option value="5k_20k">$5,000 – $20,000</option>
                <option value="20k_plus">$20,000+</option>
            </select>
        </div>
        <div class="field">
            <label>Tell us about the project</label>
            <textarea name="message" rows="5" required>{{ old('message') }}</textarea>
        </div>
        @guest
            <div class="field-row">
                <div class="field"><label>Your name</label><input type="text" name="contact_name" required></div>
                <div class="field"><label>Your email</label><input type="email" name="contact_email" required></div>
            </div>
        @endguest
        <button type="submit" class="btn btn-dark btn-block">Send request →</button>
    </form>
</div>
<div style="height:60px"></div>
@endsection
