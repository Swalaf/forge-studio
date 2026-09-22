@extends('layouts.dashboard')
@section('title', 'Content')
@section('content')
<x-page-head eyebrow="Operations" title="Content" subtitle="Copy shown across the public storefront." />
<form method="POST" action="{{ route('admin.content.update') }}" class="card card-pad" style="display:grid;gap:16px;max-width:640px">
    @csrf @method('PUT')
    @foreach ($fields as $key => $label)
        <div class="field">
            <label>{{ $label }}</label>
            <textarea name="{{ $key }}" rows="2">{{ old($key, $values[$key]) }}</textarea>
        </div>
    @endforeach
    <button type="submit" class="btn btn-dark" style="justify-self:start">Save content</button>
</form>
@endsection
