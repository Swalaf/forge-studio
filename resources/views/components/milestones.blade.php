@props(['milestones' => []])
<div class="milestones">
    @foreach ($milestones as $m)
        <div class="milestone {{ $m['status'] === 'done' ? 'is-done' : ($m['status'] === 'in_progress' ? 'is-progress' : '') }}">
            <div class="name">{{ $m['name'] }}</div>
            <div class="date">{{ $m['date_label'] }}</div>
        </div>
    @endforeach
</div>
