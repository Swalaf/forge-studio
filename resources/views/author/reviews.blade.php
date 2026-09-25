@extends('layouts.dashboard')
@section('title', 'Reviews')
@section('content')
<x-page-head eyebrow="Author workspace" title="Reviews" subtitle="What buyers say, and what you owe them a reply to." />
<x-stat-grid :stats="[
    ['k' => 'Average rating', 'v' => $avgRating, 'tone' => 'ok'],
    ['k' => 'Unanswered', 'v' => (string) $unanswered, 'tone' => $unanswered ? 'wait' : 'ok'],
]" />

<div style="display:grid;gap:12px">
    @forelse ($reviews as $review)
        <div class="card card-pad">
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <div class="dt-avatar">{{ $review->customer->initials() }}</div>
                <div style="flex:1 1 180px;min-width:0">
                    <div style="font-size:13.5px;font-weight:650">{{ $review->customer->name }}</div>
                    <div class="mono" style="font-size:10px;color:var(--muted-2);margin-top:2px">{{ $review->product->title }} · {{ $review->created_at->format('d M Y') }}</div>
                </div>
                <span style="font-size:13px;font-weight:700">★ {{ $review->rating }}</span>
                <x-pill :tone="$review->reply_body ? 'ok' : 'wait'">{{ $review->reply_body ? 'Replied' : 'Needs reply' }}</x-pill>
            </div>
            <p style="margin-top:12px;font-size:13.5px;line-height:1.65;color:#4A5262">{{ $review->body }}</p>
            @if ($review->reply_body)
                <div style="margin-top:12px;padding:12px;background:var(--border-soft);border-radius:12px;font-size:13px">{{ $review->reply_body }}</div>
            @else
                <form method="POST" action="{{ route('author.reviews.reply', $review) }}" style="margin-top:12px;display:flex;gap:9px">
                    @csrf
                    <input type="text" name="reply_body" placeholder="Write a reply…" required style="flex:1;height:38px;border:1px solid #DCDFE7;border-radius:10px;padding:0 12px">
                    <button type="submit" class="btn btn-dark btn-sm">Reply</button>
                </form>
            @endif
        </div>
    @empty
        <p style="color:var(--muted)">No reviews yet.</p>
    @endforelse
</div>
<div style="margin-top:16px">{!! $reviews->links() !!}</div>
@endsection
