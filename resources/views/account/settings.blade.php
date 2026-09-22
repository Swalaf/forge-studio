@extends('layouts.dashboard')
@section('title', 'Settings')
@section('content')
<x-page-head eyebrow="Your account" title="Account settings" subtitle="Profile and billing details." />
<form method="POST" action="{{ route('account.settings.update') }}" class="card card-pad" style="display:grid;gap:16px;max-width:520px">
    @csrf @method('PUT')
    <div class="field"><label>Full name</label><input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
    <div class="field"><label>Company</label><input type="text" name="company" value="{{ old('company', auth()->user()->company) }}"></div>
    <div class="field"><label>Country</label><input type="text" name="country" value="{{ old('country', auth()->user()->country) }}"></div>
    <button type="submit" class="btn btn-dark" style="justify-self:start">Save changes</button>
</form>
@endsection
