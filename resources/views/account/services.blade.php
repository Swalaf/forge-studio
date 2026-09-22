@extends('layouts.dashboard')
@section('title', 'Studio service orders')
@section('content')
<x-page-head eyebrow="Your account" title="Studio service orders" subtitle="Customization, installation and development work you have commissioned.">
    <x-slot:actions>
        <a href="{{ route('hire-us') }}" class="btn btn-dark">Start a new project</a>
    </x-slot:actions>
</x-page-head>

<div style="display:grid;gap:14px">
    @forelse ($projects as $p)
        <a href="{{ route('account.services.show', $p) }}" class="card card-pad" style="display:block;color:inherit">
            <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap">
                <div>
                    <span style="font-size:16px;font-weight:720;letter-spacing:-0.015em">{{ $p->title }}</span>
                    <x-pill :tone="$p->status === 'at_risk' ? 'wait' : 'info'">{{ str($p->status)->headline() }}</x-pill>
                </div>
                <div style="text-align:right">
                    <div style="font-size:18px;font-weight:800">${{ number_format($p->value_cents / 100, 0) }}</div>
                    <div style="font-size:12px;color:var(--muted)">{{ $p->billing_note }}</div>
                </div>
            </div>
            <x-milestones :milestones="$p->milestones->map(fn($m) => ['name' => $m->name, 'status' => $m->status, 'date_label' => $m->date_label])" />
        </a>
    @empty
        <p style="color:var(--muted)">No service orders yet — <a href="{{ route('hire-us') }}">hire the studio</a> for custom work.</p>
    @endforelse
</div>
@endsection
