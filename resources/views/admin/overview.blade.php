@extends('layouts.dashboard')
@section('title', 'Overview — Forge Admin')
@section('content')
<x-page-head eyebrow="Console" title="Overview" subtitle="Everything that needs a decision today, across the marketplace and the studio." />
<x-stat-grid :stats="$stats" />

<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
    <div style="flex:3 1 460px;min-width:0;display:grid;gap:16px">
        <div class="card card-pad">
            <div style="font-size:15.5px;font-weight:720;margin-bottom:14px">Top products (30d)</div>
            <div style="display:grid;gap:12px">
                @forelse ($topProducts as $p)
                    <div>
                        <div style="display:flex;justify-content:space-between;gap:10px;font-size:13px">
                            <span style="font-weight:650">{{ $p->title }}</span>
                            <span class="mono" style="color:var(--muted)">{{ $p->sales_count }} sales</span>
                        </div>
                        <div style="height:6px;border-radius:99px;background:var(--border-soft);margin-top:6px;overflow:hidden">
                            <div style="width:{{ round($p->sales_count / $maxSales * 100) }}%;height:100%;background:#0B0F19"></div>
                        </div>
                    </div>
                @empty
                    <p style="color:var(--muted);font-size:13px">No sales yet.</p>
                @endforelse
            </div>
        </div>
        <div class="card" style="overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border-soft);font-size:15.5px;font-weight:720">Needs your decision</div>
            @forelse ($decisions as $d)
                <a href="{{ $d['url'] }}" style="padding:14px 20px;border-bottom:1px solid var(--border-soft);display:flex;gap:14px;align-items:center;flex-wrap:wrap;color:inherit">
                    <div style="flex:1 1 240px;min-width:0">
                        <div style="font-size:14px;font-weight:650">{{ $d['title'] }}</div>
                        <div class="mono" style="font-size:10.5px;color:var(--muted-2);margin-top:3px">{{ $d['meta'] }}</div>
                    </div>
                    <x-pill tone="wait">{{ $d['tag'] }}</x-pill>
                    <span class="btn btn-outline btn-sm">Open</span>
                </a>
            @empty
                <p style="padding:16px 20px;color:var(--muted);font-size:13px">Nothing waiting on you right now.</p>
            @endforelse
        </div>
    </div>
    <div style="flex:1 1 280px;min-width:0;display:grid;gap:16px">
        <div class="card card-pad">
            <div style="font-size:15px;font-weight:720;margin-bottom:12px">Recent activity</div>
            <div style="display:grid;gap:10px;font-size:12.5px">
                @forelse ($recentActivity as $log)
                    <div style="border-bottom:1px solid var(--border-soft);padding-bottom:9px">
                        <div style="font-weight:650">{{ str($log->action)->headline() }}</div>
                        <div class="mono" style="color:var(--muted-2);margin-top:3px">{{ $log->actor?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <p style="color:var(--muted)">No activity logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
