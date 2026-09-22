@extends('layouts.dashboard')
@section('title', 'Review — '.$version->product->title)
@section('content')
<x-page-head eyebrow="Review decision" :title="$version->product->title.' · v'.$version->version"
    :subtitle="$version->product->author->name.' · submitted '.$version->submitted_at?->format('d M Y').' · type: '.str($version->type)->headline()" />

@if ($version->changelog)
    <div class="card card-pad">
        <div class="eyebrow">Changelog</div>
        <p style="margin-top:8px;font-size:13.5px;line-height:1.6">{{ $version->changelog }}</p>
    </div>
@endif

<div class="card card-pad">
    <form method="POST" action="{{ route('admin.review.approve', $version) }}" style="display:grid;gap:14px" id="review-form">
        @csrf
        <label class="field">
            <span>Reviewer notes (sent to author)</span>
            <textarea name="reviewer_notes" rows="4" placeholder="What must change before this can be published…"></textarea>
        </label>
        <label class="field" style="max-width:280px">
            <span>Publish as</span>
            <select name="publish_as">
                <option value="live">Live immediately</option>
                <option value="scheduled">Scheduled</option>
                <option value="hidden">Hidden / private link</option>
            </select>
        </label>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="submit" class="btn btn-dark">Approve &amp; publish</button>
            <button type="submit" formaction="{{ route('admin.review.request-changes', $version) }}" class="btn btn-outline">Request changes</button>
            <button type="submit" formaction="{{ route('admin.review.reject', $version) }}" class="btn btn-danger">Reject submission</button>
        </div>
    </form>
</div>
@endsection
