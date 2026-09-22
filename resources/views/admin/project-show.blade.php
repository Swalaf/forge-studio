@extends('layouts.dashboard')
@section('title', $project->title)
@section('content')
<x-page-head eyebrow="Studio services" :title="$project->title" :subtitle="$project->customer->name.' · '.($project->owner?->name ?? 'Unassigned')" />

<div class="card card-pad">
    <x-milestones :milestones="$project->milestones->map(fn($m) => ['name' => $m->name, 'status' => $m->status, 'date_label' => $m->date_label])" />

    <div style="margin-top:20px;display:grid;gap:10px">
        @foreach ($project->milestones as $m)
            <form method="POST" action="{{ route('admin.projects.milestones.update', [$project, $m]) }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--border-soft);padding-top:10px">
                @csrf
                <span style="flex:1 1 160px;font-size:13.5px;font-weight:650">{{ $m->name }}</span>
                <select name="status" style="height:36px;border:1px solid #DCDFE7;border-radius:9px">
                    @foreach (['upcoming','in_progress','done'] as $s)
                        <option value="{{ $s }}" @selected($m->status === $s)>{{ str($s)->headline() }}</option>
                    @endforeach
                </select>
                <input type="text" name="date_label" value="{{ $m->date_label }}" placeholder="date label" style="height:36px;border:1px solid #DCDFE7;border-radius:9px;padding:0 10px;width:150px">
                <button type="submit" class="btn btn-outline btn-sm">Save</button>
            </form>
        @endforeach
    </div>
</div>

<form method="POST" action="{{ route('admin.projects.update', $project) }}" class="card card-pad" style="display:grid;gap:14px;max-width:480px">
    @csrf @method('PUT')
    <div class="field">
        <label>Status</label>
        <select name="status">
            @foreach (['active','at_risk','launching','completed'] as $s)
                <option value="{{ $s }}" @selected($project->status === $s)>{{ str($s)->headline() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Value (cents)</label><input type="number" name="value_cents" value="{{ $project->value_cents }}"></div>
    <div class="field"><label>Billing note</label><input type="text" name="billing_note" value="{{ $project->billing_note }}"></div>
    <button type="submit" class="btn btn-dark" style="justify-self:start">Save project</button>
</form>
@endsection
