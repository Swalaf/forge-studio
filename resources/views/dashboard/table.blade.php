{{-- Generic tabs + stat-grid + data-table section, shared by admin/author/account
     table-driven dashboard pages (products, orders, licenses, tickets, etc). --}}
@extends('layouts.dashboard')
@section('title', $title.' — '.($dashTitle ?? 'Forge Market'))
@section('content')

<x-page-head :eyebrow="$crumb ?? null" :title="$title" :subtitle="$subtitle ?? null">
    @if (!empty($actionsHtml))
        <x-slot:actions>{!! $actionsHtml !!}</x-slot:actions>
    @endif
</x-page-head>

@if (!empty($stats))
    <x-stat-grid :stats="$stats" />
@endif

@if (isset($kanban))
    <div class="kanban">
        @foreach ($kanban as $col)
            <div class="kanban-col">
                <div class="kanban-col-head">
                    <span style="font-size:13.5px;font-weight:720">{{ $col['name'] }}</span>
                    <span class="mono" style="font-size:11px;color:var(--muted-2)">{{ $col['value'] }}</span>
                </div>
                @foreach ($col['cards'] as $card)
                    <a href="{{ $card['url'] }}" class="kanban-card" style="display:block;color:inherit">
                        <div style="font-size:13.5px;font-weight:650;letter-spacing:-0.01em">{{ $card['title'] }}</div>
                        <div class="mono" style="font-size:10px;color:var(--muted-2);margin-top:4px">{{ $card['client'] }}</div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
                            <span style="font-size:12.5px;font-weight:700">{{ $card['value'] }}</span>
                            <x-pill :tone="$card['tone']">{{ $card['owner'] }}</x-pill>
                        </div>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
@else
    <x-data-table
        :tabs="$tabs ?? []"
        :col-a="$colA ?? 'Item'"
        :col-b="$colB ?? null"
        :col-c="$colC ?? null"
        :rows="$rows ?? []"
        :empty-text="$emptyText ?? 'Nothing here yet.'"
        :panel-title="$panelTitle ?? null"
        :panel-rows="$panelRows ?? []"
        :panel-action="$panelAction ?? null"
    >
        @isset($pagination)
            <x-slot:pagination>{!! $pagination !!}</x-slot:pagination>
        @endisset
    </x-data-table>
@endif

@isset($footerHtml)
    <div style="margin-top:4px">{!! $footerHtml !!}</div>
@endisset

@endsection
