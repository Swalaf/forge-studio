@extends('layouts.dashboard')
@section('title', 'Open a ticket')
@section('content')
<x-page-head eyebrow="Support" title="Open a ticket" subtitle="We'll route it to the studio or the product's author." />
<form method="POST" action="{{ route('account.support.store') }}" class="card card-pad" style="display:grid;gap:14px;max-width:560px">
    @csrf
    <div class="field"><label>Subject</label><input type="text" name="subject" required></div>
    <div class="field">
        <label>Product (optional)</label>
        <select name="product_id">
            <option value="">General</option>
            @foreach ($products as $p)
                <option value="{{ $p->id }}">{{ $p->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Describe the issue</label><textarea name="body" rows="4" required></textarea></div>
    <button type="submit" class="btn btn-dark" style="justify-self:start">Open ticket</button>
</form>
@endsection
