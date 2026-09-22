@extends('layouts.dashboard')
@section('title', 'Settings')
@section('content')
<x-page-head eyebrow="Operations" title="Settings" subtitle="Marketplace-wide defaults." />
<form method="POST" action="{{ route('admin.settings.update') }}" class="card card-pad" style="display:grid;gap:16px;max-width:520px">
    @csrf @method('PUT')
    @foreach ($fields as $key => $label)
        <div class="field">
            <label>{{ $label }}</label>
            <input type="text" name="{{ $key }}" value="{{ old($key, $values[$key]) }}">
        </div>
    @endforeach
    <button type="submit" class="btn btn-dark" style="justify-self:start">Save settings</button>
</form>
@endsection
