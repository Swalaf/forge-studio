@extends('layouts.dashboard')
@section('title', 'Audit log')
@section('content')
<x-page-head eyebrow="Operations" title="Audit log" subtitle="Every admin action, automatically recorded." />
<div class="card" style="overflow:hidden">
    @forelse ($logs as $log)
        <div style="padding:13px 20px;border-bottom:1px solid var(--border-soft);display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap">
            <div>
                <div style="font-size:13.5px;font-weight:650">{{ str($log->action)->headline() }}</div>
                <div class="mono" style="font-size:10.5px;color:var(--muted-2);margin-top:3px">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '' }}</div>
            </div>
            <div style="text-align:right;font-size:12.5px;color:var(--muted)">
                {{ $log->actor?->name ?? 'System' }}<br>{{ $log->created_at->format('d M Y H:i') }}
            </div>
        </div>
    @empty
        <p style="padding:16px 20px;color:var(--muted)">Nothing logged yet.</p>
    @endforelse
</div>
<div style="margin-top:16px">{!! $logs->links() !!}</div>
@endsection
