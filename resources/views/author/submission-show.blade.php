@extends('layouts.dashboard')
@section('title', $version->product->title.' — submission')
@section('content')
<x-page-head eyebrow="Submissions" :title="$version->product->title.' · v'.$version->version"
    :subtitle="'Status: '.str($version->status)->headline().($version->submitted_at ? ' · submitted '.$version->submitted_at->format('d M Y') : '')">
    <x-slot:actions>
        @if (in_array($version->status, ['pending', 'changes_requested']))
            <form method="POST" action="{{ route('author.submissions.withdraw', $version) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="btn btn-outline">Withdraw</button></form>
        @endif
    </x-slot:actions>
</x-page-head>

@if ($version->reviewer_notes)
    <div class="card card-pad">
        <div class="eyebrow">Reviewer notes</div>
        <p style="margin-top:8px;font-size:13.5px;line-height:1.6">{{ $version->reviewer_notes }}</p>
        @if ($version->reviewer)
            <div class="mono" style="font-size:11px;color:var(--muted-2);margin-top:10px">— {{ $version->reviewer->name }}</div>
        @endif
    </div>
@endif

@if ($version->changelog)
    <div class="card card-pad">
        <div class="eyebrow">Your changelog</div>
        <p style="margin-top:8px;font-size:13.5px;line-height:1.6">{{ $version->changelog }}</p>
    </div>
@endif
@endsection
