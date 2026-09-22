@extends('layouts.dashboard')
@section('title', $author->name)
@section('content')
<x-page-head eyebrow="Marketplace" :title="$author->name" :subtitle="($author->country ?? '—').' · '.$author->products_count.' products · joined '.$author->created_at->format('M Y')">
    <x-slot:actions>
        @if ($author->standing !== 'suspended')
            <form method="POST" action="{{ route('admin.authors.suspend', $author) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="btn btn-outline">Suspend</button></form>
        @endif
    </x-slot:actions>
</x-page-head>

<div class="card card-pad" style="max-width:480px">
    <form method="POST" action="{{ route('admin.authors.tier', $author) }}" style="display:grid;gap:14px">
        @csrf @method('PUT')
        <div class="field">
            <label>Commission tier</label>
            <select name="author_tier">
                @foreach (['standard' => 'Standard — 70%', 'exclusive' => 'Exclusive — 80%', 'studio_original' => 'Studio original — 100%', 'probation' => 'Probation — 60%'] as $val => $label)
                    <option value="{{ $val }}" @selected($author->author_tier === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Commission % (author share)</label><input type="number" name="commission_pct" value="{{ $author->commission_pct }}" min="0" max="100"></div>
        <button type="submit" class="btn btn-dark" style="justify-self:start">Update tier</button>
    </form>
</div>

<div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border-soft);font-weight:720">Products</div>
    @forelse ($products as $p)
        <div style="padding:13px 20px;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between">
            <span>{{ $p->title }}</span><span class="mono" style="color:var(--muted)">{{ $p->priceFormatted() }}</span>
        </div>
    @empty
        <p style="padding:16px 20px;color:var(--muted)">No products yet.</p>
    @endforelse
</div>
@endsection
