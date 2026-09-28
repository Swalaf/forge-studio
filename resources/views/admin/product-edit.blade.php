@extends('layouts.dashboard')
@section('title', $product->exists ? 'Edit — '.$product->title : 'New studio product')
@section('content')
<x-page-head eyebrow="Marketplace" :title="$product->exists ? $product->title : 'New studio product'" :subtitle="$product->exists ? 'Shape your listing, manage its storefront and control visibility.' : 'Build a Studio original with a clear preview, pricing and a private release.'" />

<div class="listing-workspace">
<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="card listing-form">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="listing-section-head">
        <div><div class="eyebrow eyebrow-accent">01 / Listing details</div><h2>{{ $product->exists ? 'Edit this product' : 'Introduce your product' }}</h2><p>Help buyers understand what this software does and why it matters.</p></div>
        @if ($product->exists)
            <x-pill :tone="match ($product->status) { 'live' => 'ok', 'in_review', 'changes_requested' => 'wait', default => 'info' }">{{ str($product->status)->headline() }}</x-pill>
        @else
            <x-pill tone="info">Studio original</x-pill>
        @endif
    </div>
    <div class="field"><label for="studio-title">Title</label><input id="studio-title" type="text" name="title" value="{{ old('title', $product->title) }}" placeholder="e.g. Atlas UI Kit" required></div>
    <div class="field"><label for="studio-tagline">Tagline</label><input id="studio-tagline" type="text" name="tagline" value="{{ old('tagline', $product->tagline) }}" placeholder="A short, compelling summary"></div>
    <div class="field"><label for="studio-description">Description</label><textarea id="studio-description" name="description" rows="6" placeholder="What does it do, and who is it for?">{{ old('description', $product->description) }}</textarea></div>
    <div class="field-row">
        <div class="field">
            <label for="studio-category">Category</label>
            <select id="studio-category" name="category_id">
                <option value="">Choose a category</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) old('category_id', $product->category_id) === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="studio-demo">Demo URL</label><input id="studio-demo" type="url" name="demo_url" value="{{ old('demo_url', $product->demo_url) }}" placeholder="https://"></div>
    </div>

    @if (! $product->exists || $product->is_studio_original)
        <x-product-banner-field :product="$product" />
    @endif

    <div class="listing-divider"></div>
    <div class="listing-section-head"><div><div class="eyebrow eyebrow-accent">{{ $product->exists && ! $product->is_studio_original ? '02' : '03' }} / Pricing</div><h2>Set your price</h2><p>Enter amounts in USD cents. For example, 2900 means $29.00.</p></div></div>
    <div class="field-row">
        <div class="field"><label for="studio-price">Regular price (cents)</label><input id="studio-price" type="number" min="0" name="price_cents" value="{{ old('price_cents', $product->price_cents) }}" required></div>
        <div class="field"><label for="studio-extended">Extended price (cents)</label><input id="studio-extended" type="number" min="0" name="extended_price_cents" value="{{ old('extended_price_cents', $product->extended_price_cents) }}" placeholder="Optional"></div>
    </div>

    @if (! $product->exists || $product->is_studio_original)
        <div class="listing-divider"></div>
        <div class="listing-section-head"><div><div class="eyebrow eyebrow-accent">04 / Release file</div><h2>Attach the build</h2><p>The private ZIP must exist before this product can go live.</p></div></div>
        <div class="listing-upload"><div class="listing-upload-icon" aria-hidden="true">ZIP</div><div class="field"><label for="studio-release">Private release ZIP (max 100 MB)</label><input id="studio-release" type="file" name="release_zip" accept=".zip,application/zip"><span class="field-hint">{{ $product->exists ? 'Current version: '.$product->current_version.'. Leave empty to keep the existing file.' : 'Initial version: 1.0.0. You can also upload after creating the draft.' }} Never place paid software in public storage.</span></div></div>
    @endif

    <div class="listing-divider"></div>
    <div class="listing-section-head"><div><div class="eyebrow eyebrow-accent">{{ $product->exists && ! $product->is_studio_original ? '03' : '05' }} / Visibility</div><h2>Choose when it appears</h2><p>Drafts stay off the storefront until you publish them.</p></div></div>
    @if ($product->exists)
        <div class="field"><label for="studio-status">Product status</label><select id="studio-status" name="status">
            @foreach (['draft','in_review','changes_requested','rejected','scheduled','live','hidden'] as $s)
                <option value="{{ $s }}" @selected(old('status', $product->status) === $s)>{{ str($s)->headline() }}</option>
            @endforeach
        </select></div>
    @endif
    <label class="listing-feature-toggle">
        <span><strong>Feature on homepage</strong><small>Highlight this product in the storefront's featured selection when live.</small></span>
        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))>
    </label>
    <div class="listing-actions"><button type="submit" class="btn btn-dark">{{ $product->exists ? 'Save changes' : 'Create draft' }}</button><a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Back to products</a></div>
</form>
</div>
@endsection
