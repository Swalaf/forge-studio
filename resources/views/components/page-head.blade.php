@props(['eyebrow' => null, 'title', 'subtitle' => null])
<div class="page-head">
    <div>
        @if ($eyebrow)
            <div class="eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="actions">{{ $actions }}</div>
    @endisset
</div>
