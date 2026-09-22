@extends('layouts.dashboard')
@section('title', 'Settings')
@section('content')
<x-page-head eyebrow="Author workspace" title="Author settings" subtitle="Public profile and payout details." />
<form method="POST" action="{{ route('author.settings.update') }}" class="card card-pad" style="display:grid;gap:16px;max-width:520px">
    @csrf @method('PUT')
    <div class="field"><label>Display name</label><input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
    <div class="field"><label>Company</label><input type="text" name="company" value="{{ old('company', auth()->user()->company) }}"></div>
    <div class="field"><label>Country</label><input type="text" name="country" value="{{ old('country', auth()->user()->country) }}"></div>
    <div class="field"><label>Payout method</label><input type="text" name="payout_method" value="{{ old('payout_method', auth()->user()->payout_method) }}" placeholder="SEPA ···8841"></div>
    <button type="submit" class="btn btn-dark" style="justify-self:start">Save changes</button>
</form>
@endsection
