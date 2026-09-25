@props([
    'tabs' => [],
    'colA' => 'Item',
    'colB' => null,
    'colC' => null,
    'rows' => [],
    'emptyText' => 'Nothing here yet.',
    'panelTitle' => null,
    'panelRows' => [],
    'panelAction' => null,
])
{{-- $rows: array of ['avatarKind' => 'thumb'|'person', 'avatarText' => '', 'title', 'meta', 'b', 'c',
     'status', 'tone', 'primary' => ['label','url','method'?], 'secondary' => [...] ] --}}
<div class="dt-wrap">
    <div class="dt-main">
        @if (count($tabs))
            <div class="dt-tabs">
                @foreach ($tabs as $tab)
                    <span class="dt-tab {{ $tab['active'] ? 'is-active' : '' }}">{{ $tab['label'] }}</span>
                @endforeach
            </div>
        @endif

        @if (count($rows))
            <div class="dt-head">
                <span>{{ $colA }}</span>
                <span>{{ $colB }}</span>
                <span>{{ $colC }}</span>
                <span>Status</span>
                <span style="text-align:right">Actions</span>
            </div>
            @foreach ($rows as $row)
                <div class="dt-row">
                    <div style="display:flex;gap:11px;align-items:center;min-width:0">
                        @if (($row['avatarKind'] ?? 'thumb') === 'person')
                            <div class="dt-avatar">{{ $row['avatarText'] ?? '' }}</div>
                        @else
                            <div class="dt-thumb"></div>
                        @endif
                        <div style="min-width:0">
                            <div class="dt-title">{{ $row['title'] }}</div>
                            @if (!empty($row['meta']))
                                <div class="dt-meta">{{ $row['meta'] }}</div>
                            @endif
                        </div>
                    </div>
                    <div style="font-size:13px;color:#4A5262;min-width:0">{{ $row['b'] ?? '' }}</div>
                    <div style="font-size:13px;color:#4A5262;min-width:0">{{ $row['c'] ?? '' }}</div>
                    <div>
                        @if (!empty($row['status']))
                            <x-pill :tone="$row['tone'] ?? 'info'">{{ $row['status'] }}</x-pill>
                        @endif
                    </div>
                    <div class="dt-cell-actions">
                        @if (!empty($row['primary']))
                            @if (($row['primary']['method'] ?? 'GET') === 'GET')
                                <a href="{{ $row['primary']['url'] }}" class="btn btn-dark btn-sm">{{ $row['primary']['label'] }}</a>
                            @else
                                <form method="POST" action="{{ $row['primary']['url'] }}">
                                    @csrf
                                    @if ($row['primary']['method'] !== 'POST')
                                        @method($row['primary']['method'])
                                    @endif
                                    <button type="submit" class="btn btn-dark btn-sm">{{ $row['primary']['label'] }}</button>
                                </form>
                            @endif
                        @endif
                        @if (!empty($row['secondary']))
                            @if (($row['secondary']['method'] ?? 'GET') === 'GET')
                                <a href="{{ $row['secondary']['url'] }}" class="btn btn-outline btn-sm">{{ $row['secondary']['label'] }}</a>
                            @else
                                <form method="POST" action="{{ $row['secondary']['url'] }}">
                                    @csrf
                                    @if ($row['secondary']['method'] !== 'POST')
                                        @method($row['secondary']['method'])
                                    @endif
                                    <button type="submit" class="btn btn-outline btn-sm">{{ $row['secondary']['label'] }}</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="dt-empty">{{ $emptyText }}</div>
        @endif

        @isset($pagination)
            <div style="padding:13px 18px">{!! $pagination !!}</div>
        @endisset
    </div>

    @if ($panelTitle)
        <aside class="dt-side">
            <div class="dt-panel">
                <h4>{{ $panelTitle }}</h4>
                <div class="dt-panel-rows">
                    @foreach ($panelRows as $p)
                        <div class="dt-panel-row"><span>{{ $p[0] }}</span><span>{{ $p[1] }}</span></div>
                    @endforeach
                </div>
                @if ($panelAction)
                    <a href="{{ $panelAction['url'] }}" class="btn btn-outline btn-block btn-sm" style="margin-top:14px">{{ $panelAction['label'] }}</a>
                @endif
            </div>
            {{ $slot ?? '' }}
        </aside>
    @endif
</div>
