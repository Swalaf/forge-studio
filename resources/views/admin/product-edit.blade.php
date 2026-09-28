@extends('layouts.dashboard')
@section('title', $product->exists ? 'Edit — '.$product->title : 'New studio product')
@section('content')
<x-page-head eyebrow="Marketplace" :title="$product->exists ? $product->title : 'New studio product'" subtitle="Create a studio listing, upload its ZIP, then publish when ready." />

<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="card card-pad" style="display:grid;gap:16px;max-width:720px">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="field"><label>Title</label><input type="text" name="title" value="{{ old('title', $product->title) }}" required></div>
    <div class="field"><label>Tagline</label><input type="text" name="tagline" value="{{ old('tagline', $product->tagline) }}"></div>
    <div class="field"><label>Description</label><textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea></div>
    <div class="field-row">
        <div class="field">
            <label>Category</label>
            <select name="category_id">
                <option value="">—</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) old('category_id', $product->category_id) === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Price (cents)</label><input type="number" name="price_cents" value="{{ old('price_cents', $product->price_cents) }}" required></div>
        <div class="field"><label>Extended price (cents)</label><input type="number" name="extended_price_cents" value="{{ old('extended_price_cents', $product->extended_price_cents) }}"></div>
    </div>
    <div class="field"><label>Demo URL</label><input type="url" name="demo_url" value="{{ old('demo_url', $product->demo_url) }}"></div>
    @if (! $product->exists || $product->is_studio_original)
        <div class="field">
            <label>Private release ZIP (max 100 MB)</label>
            <input type="file" name="release_zip" accept=".zip,application/zip">
            <small>A ZIP must exist before publishing; leave empty if one is already stored. {{ $product->exists ? 'Current version: '.$product->current_version : 'Initial version: 1.0.0' }}. Files are kept outside the public web directory.</small>
        </div>
    @endif
    @if ($product->exists)
        <div class="field">
            <label>Status</label>
            <select name="status">
                @foreach (['draft','in_review','changes_requested','rejected','scheduled','live','hidden'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $product->status) === $s)>{{ str($s)->headline() }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <label style="display:flex;gap:9px;align-items:center;font-size:13.5px">
        <input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Feature on homepage
    </label>
    <button type="submit" class="btn btn-dark" style="justify-self:start">{{ $product->exists ? 'Save changes' : 'Create draft' }}</button>
</form>
@endsection
