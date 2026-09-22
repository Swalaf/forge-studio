@props(['stats' => []])
{{-- $stats: array of ['k' => string, 'v' => string, 'delta' => ?string, 'tone' => ok|wait|bad|accent|null] --}}
<div class="stat-grid">
    @foreach ($stats as $s)
        <div class="stat-card">
            <div class="k">{{ $s['k'] }}</div>
            <div class="v">{{ $s['v'] }}</div>
            @if (!empty($s['delta']))
                <div class="delta {{ $s['tone'] ?? '' }}">{{ $s['delta'] }}</div>
            @endif
        </div>
    @endforeach
</div>
