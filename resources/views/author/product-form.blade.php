@extends('layouts.dashboard')
@section('title', $product->exists ? 'Edit — '.$product->title : 'New listing')
@section('content')
<x-page-head eyebrow="Author workspace" :title="$product->exists ? $product->title : 'New listing'" subtitle="Build your listing, add a private release, then send it to the studio for review." />

<div class="listing-workspace">
<form method="POST" action="{{ $product->exists ? route('author.products.update', $product) : route('author.products.store') }}" enctype="multipart/form-data" class="card listing-form">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="listing-section-head">
        <div><div class="eyebrow eyebrow-accent">01 / Listing details</div><h2>{{ $product->exists ? 'Edit your product' : 'Introduce your product' }}</h2><p>Give buyers a clear picture of what you have built.</p></div>
        @if ($product->exists) <x-pill :tone="match ($product->status) { 'live' => 'ok', 'in_review', 'changes_requested' => 'wait', default => 'info' }">{{ str($product->status)->headline() }}</x-pill> @endif
    </div>
    <div class="field"><label for="listing-title">Title</label><input id="listing-title" type="text" name="title" value="{{ old('title', $product->title) }}" placeholder="e.g. Atlas UI Kit" required></div>
    <div class="field"><label for="listing-tagline">Tagline</label><input id="listing-tagline" type="text" name="tagline" value="{{ old('tagline', $product->tagline) }}" placeholder="A short, compelling summary"></div>
    <div class="field"><label for="listing-description">Description</label><textarea id="listing-description" name="description" rows="6" placeholder="What does it do, and who is it for?">{{ old('description', $product->description) }}</textarea></div>
    <div class="field-row">
        <div class="field">
            <label for="listing-category">Category</label>
            <select id="listing-category" name="category_id">
                <option value="">Choose a category</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) old('category_id', $product->category_id) === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="listing-demo">Demo URL</label><input id="listing-demo" type="url" name="demo_url" value="{{ old('demo_url', $product->demo_url) }}" placeholder="https://"></div>
    </div>
    <div class="listing-divider"></div>
    <div class="listing-section-head"><div><div class="eyebrow eyebrow-accent">02 / Pricing</div><h2>Set your price</h2><p>Enter amounts in USD cents. For example, 2900 means $29.00.</p></div></div>
    <div class="field-row">
        <div class="field"><label for="listing-price">Regular price (cents)</label><input id="listing-price" type="number" min="0" name="price_cents" value="{{ old('price_cents', $product->price_cents) }}" required></div>
        <div class="field"><label for="listing-extended">Extended price (cents)</label><input id="listing-extended" type="number" min="0" name="extended_price_cents" value="{{ old('extended_price_cents', $product->extended_price_cents) }}" placeholder="Optional"></div>
    </div>
    @if (! $product->exists || (! $product->published_at && $product->status !== 'in_review'))
        <div class="listing-divider"></div>
        <div class="listing-section-head"><div><div class="eyebrow eyebrow-accent">03 / Release file</div><h2>Attach your build</h2><p>A private ZIP is needed before you can submit a version for review.</p></div></div>
        <div class="listing-upload"><div class="listing-upload-icon" aria-hidden="true">ZIP</div><div class="field"><label for="listing-file">Product ZIP (max 100 MB)</label><input id="listing-file" type="file" name="release_zip" accept=".zip,application/zip"><span class="field-hint">{{ $product->exists ? 'Leave empty to keep the existing draft file.' : 'You can also upload it after saving your draft.' }} Downloads are private and available only to licensed buyers.</span></div></div>
    @endif
    <div class="listing-actions"><button type="submit" class="btn btn-dark">{{ $product->exists ? 'Save changes' : 'Create draft' }}</button><a href="{{ route('author.products.index') }}" class="btn btn-ghost">Back to products</a></div>
</form>

@if ($product->exists)
    @if ($product->status === 'in_review')
        <div class="card listing-review"><x-pill tone="wait">In review</x-pill><div><h2>Submitted to the studio</h2><p>Your version is being reviewed. You can still edit the listing details above; new files and submissions can be sent after the review is resolved.</p></div></div>
    @else
        <form method="POST" action="{{ route('author.products.submit-version', $product) }}" enctype="multipart/form-data" class="card listing-form">
            @csrf
            <div class="listing-section-head"><div><div class="eyebrow eyebrow-accent">Next step</div><h2>Submit a version for review</h2><p>Upload the release for this version. Your current live download stays unchanged until approval.</p></div></div>
            <div class="field-row">
                <div class="field"><label for="version-number">Version</label><input id="version-number" type="text" name="version" value="{{ old('version', $product->published_at ? '' : $product->current_version) }}" placeholder="1.0.0" required><span class="field-hint">Use three numbers, such as 1.0.0.</span></div>
                <div class="field"><label for="version-type">Type</label><select id="version-type" name="type"><option value="new" @selected(old('type') === 'new')>New product</option><option value="minor" @selected(old('type') === 'minor')>Minor update</option><option value="patch" @selected(old('type') === 'patch')>Patch</option></select></div>
            </div>
            <div class="field"><label for="version-changelog">Changelog</label><textarea id="version-changelog" name="changelog" rows="3" placeholder="What changed in this release?">{{ old('changelog') }}</textarea></div>
            <div class="listing-upload"><div class="listing-upload-icon" aria-hidden="true">ZIP</div><div class="field"><label for="version-file">Version ZIP (max 100 MB)</label><input id="version-file" type="file" name="release_zip" accept=".zip,application/zip"><span class="field-hint">Required unless a ZIP for this version is already stored privately.</span></div></div>
            <div class="listing-actions"><button type="submit" class="btn btn-outline">Submit for review</button></div>
        </form>
    @endif
@endif
</div>
@endsection
