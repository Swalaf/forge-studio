@extends('layouts.dashboard')
@section('title', $product->exists ? 'Edit — '.$product->title : 'New listing')
@section('content')
<x-page-head eyebrow="Author workspace" :title="$product->exists ? $product->title : 'New listing'" subtitle="Draft your listing, then submit a version for studio review." />

<form method="POST" action="{{ $product->exists ? route('author.products.update', $product) : route('author.products.store') }}" class="card card-pad" style="display:grid;gap:16px;max-width:720px">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="field"><label>Title</label><input type="text" name="title" value="{{ old('title', $product->title) }}" required></div>
    <div class="field"><label>Tagline</label><input type="text" name="tagline" value="{{ old('tagline', $product->tagline) }}"></div>
    <div class="field"><label>Description</label><textarea name="description" rows="5">{{ old('description', $product->description) }}</textarea></div>
    <div class="field-row">
        <div class="field">
            <label>Category</label>
            <select name="category_id">
                <option value="">—</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected($product->category_id === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Regular price (cents)</label><input type="number" name="price_cents" value="{{ old('price_cents', $product->price_cents) }}" required></div>
        <div class="field"><label>Extended price (cents)</label><input type="number" name="extended_price_cents" value="{{ old('extended_price_cents', $product->extended_price_cents) }}"></div>
    </div>
    <div class="field"><label>Demo URL</label><input type="url" name="demo_url" value="{{ old('demo_url', $product->demo_url) }}"></div>
    <button type="submit" class="btn btn-dark" style="justify-self:start">{{ $product->exists ? 'Save changes' : 'Create draft' }}</button>
</form>

@if ($product->exists)
    <form method="POST" action="{{ route('author.products.submit-version', $product) }}" class="card card-pad" style="display:grid;gap:14px;max-width:720px">
        @csrf
        <div style="font-size:15px;font-weight:720">Submit a version for review</div>
        <div class="field-row">
            <div class="field"><label>Version</label><input type="text" name="version" placeholder="1.0.0" required></div>
            <div class="field">
                <label>Type</label>
                <select name="type"><option value="new">New product</option><option value="minor">Minor update</option><option value="patch">Patch</option></select>
            </div>
        </div>
        <div class="field"><label>Changelog</label><textarea name="changelog" rows="3"></textarea></div>
        <button type="submit" class="btn btn-outline" style="justify-self:start">Submit for review</button>
    </form>
@endif
@endsection
