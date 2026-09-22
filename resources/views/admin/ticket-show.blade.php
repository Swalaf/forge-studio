@extends('layouts.dashboard')
@section('title', $ticket->subject)
@section('content')
<x-page-head eyebrow="Operations" :title="$ticket->subject" :subtitle="'Opened by '.$ticket->opener->name.' · '.($ticket->product?->title ?? 'General')">
    <x-slot:actions>
        @if ($ticket->status !== 'solved')
            <form method="POST" action="{{ route('admin.tickets.close', $ticket) }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="btn btn-outline">Mark solved</button></form>
        @endif
    </x-slot:actions>
</x-page-head>

<div class="card" style="overflow:hidden">
    @foreach ($ticket->messages as $m)
        <div style="padding:14px 20px;border-bottom:1px solid var(--border-soft)">
            <div style="font-size:13px;font-weight:650">{{ $m->author->name }} <span class="mono" style="font-weight:400;color:var(--muted-2)">· {{ $m->created_at->diffForHumans() }}</span></div>
            <p style="margin-top:6px;font-size:13.5px;line-height:1.6">{{ $m->body }}</p>
        </div>
    @endforeach
</div>

<form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="card card-pad" style="display:grid;gap:12px">
    @csrf
    <textarea name="body" rows="3" placeholder="Reply to this ticket…" required></textarea>
    <button type="submit" class="btn btn-dark" style="justify-self:start">Send reply</button>
</form>
@endsection
