@extends('layouts.dashboard')
@section('title', 'Overview — Author workspace')
@section('content')
<x-page-head eyebrow="Author workspace" title="Author overview" subtitle="Sales, review status and payouts for your listings.">
    <x-slot:actions>
        <a href="{{ route('author.products.create') }}" class="btn btn-dark">Submit a product</a>
    </x-slot:actions>
</x-page-head>
<x-stat-grid :stats="$stats" />

<div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
    <div style="flex:3 1 420px;min-width:0;display:grid;gap:16px">
        <div class="card card-pad">
            <div style="font-size:15.5px;font-weight:720;margin-bottom:14px">Best sellers</div>
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
                    <p style="color:var(--muted);font-size:13px">No products yet — submit your first listing.</p>
                @endforelse
            </div>
        </div>
        <div class="card" style="overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border-soft);font-size:15.5px;font-weight:720">Needs your action</div>
            @forelse ($needsAction as $a)
                <a href="{{ $a['url'] }}" style="padding:13px 20px;border-bottom:1px solid var(--border-soft);display:flex;gap:12px;align-items:center;flex-wrap:wrap;color:inherit">
                    <x-pill tone="wait">{{ $a['tag'] }}</x-pill>
                    <div style="flex:1 1 220px;min-width:0">
                        <div style="font-size:13.5px;font-weight:650">{{ $a['title'] }}</div>
                        <div class="mono" style="font-size:10px;color:var(--muted-2);margin-top:3px">{{ $a['meta'] }}</div>
                    </div>
                </a>
            @empty
                <p style="padding:16px 20px;color:var(--muted);font-size:13px">Nothing waiting on you.</p>
            @endforelse
        </div>
    </div>
    <div style="flex:1 1 260px;min-width:0">
        <div style="background:#0B0F19;border-radius:18px;padding:20px;color:#fff">
            <div class="eyebrow" style="color:#8A93A8">Next payout</div>
            <div style="font-size:30px;font-weight:800;letter-spacing:-0.03em;margin-top:8px">
                {{ $nextPayout ? '$'.number_format($nextPayout->amount_cents / 100, 0) : '$0' }}
            </div>
            <div style="font-size:12.5px;color:#B9C0D0;margin-top:4px">
                {{ $nextPayout?->period_end->format('d M Y') ?? 'No payout scheduled yet' }}
            </div>
        </div>
    </div>
</div>
@endsection
