@extends('layouts.dashboard')
@section('title', 'Edit — '.$product->title)
@section('content')
<x-page-head eyebrow="Marketplace" :title="$product->title" subtitle="Edit listing details and visibility." />

<form method="POST" action="{{ route('admin.products.update', $product) }}" class="card card-pad" style="display:grid;gap:16px;max-width:720px">
    @csrf @method('PUT')
    <div class="field"><label>Title</label><input type="text" name="title" value="{{ old('title', $product->title) }}" required></div>
    <div class="field"><label>Tagline</label><input type="text" name="tagline" value="{{ old('tagline', $product->tagline) }}"></div>
    <div class="field"><label>Description</label><textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea></div>
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
        <div class="field"><label>Price (cents)</label><input type="number" name="price_cents" value="{{ old('price_cents', $product->price_cents) }}" required></div>
        <div class="field">
            <label>Status</label>
            <select name="status">
                @foreach (['draft','in_review','changes_requested','rejected','scheduled','live','hidden'] as $s)
                    <option value="{{ $s }}" @selected($product->status === $s)>{{ str($s)->headline() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <label style="display:flex;gap:9px;align-items:center;font-size:13.5px">
        <input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Feature on homepage
    </label>
    <button type="submit" class="btn btn-dark" style="justify-self:start">Save changes</button>
</form>
@endsection
