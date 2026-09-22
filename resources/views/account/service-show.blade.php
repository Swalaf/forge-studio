@extends('layouts.dashboard')
@section('title', $project->title)
@section('content')
<x-page-head eyebrow="Service orders" :title="$project->title" :subtitle="'Owner: '.($project->owner?->name ?? 'Forge Studio')" />
<div class="card card-pad">
    <x-milestones :milestones="$project->milestones->map(fn($m) => ['name' => $m->name, 'status' => $m->status, 'date_label' => $m->date_label])" />
</div>
@endsection
