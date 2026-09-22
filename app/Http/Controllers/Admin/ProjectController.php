<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ServiceProject;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $columns = ['active' => 'Active', 'at_risk' => 'At risk', 'launching' => 'Launching', 'completed' => 'Completed'];
        $projects = ServiceProject::with('customer')->get()->groupBy('status');

        $kanban = collect($columns)->map(fn ($name, $status) => [
            'name' => $name,
            'value' => (string) $projects->get($status, collect())->count(),
            'cards' => $projects->get($status, collect())->map(fn ($p) => [
                'title' => $p->title,
                'client' => $p->customer->company ?: $p->customer->name,
                'value' => '$'.number_format($p->value_cents / 100, 0),
                'owner' => $p->owner?->name ?? 'Unassigned',
                'tone' => $status === 'at_risk' ? 'wait' : 'info',
                'url' => route('admin.projects.show', $p),
            ])->values(),
        ])->values();

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('projects'),
            'crumb' => 'Studio services', 'title' => 'Delivery projects', 'subtitle' => 'Signed work in flight — milestones, owners and handover state.',
            'stats' => [
                ['k' => 'Active projects', 'v' => (string) ServiceProject::whereIn('status', ['active', 'at_risk', 'launching'])->count(), 'tone' => 'ok'],
                ['k' => 'At risk', 'v' => (string) ServiceProject::where('status', 'at_risk')->count(), 'tone' => ServiceProject::where('status', 'at_risk')->count() ? 'wait' : 'ok'],
                ['k' => 'Billable booked', 'v' => '$'.number_format(ServiceProject::sum('value_cents') / 100, 0), 'tone' => 'ok'],
            ],
            'kanban' => $kanban,
        ]);
    }

    public function show(ServiceProject $project): View
    {
        return view('admin.project-show', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('projects'),
            'project' => $project->load('customer', 'owner', 'milestones'),
        ]);
    }

    public function update(Request $request, ServiceProject $project): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,at_risk,launching,completed'],
            'value_cents' => ['required', 'integer', 'min:0'],
            'billing_note' => ['nullable', 'string', 'max:255'],
        ]);
        $project->update($data);
        AuditLog::record('service_project.updated', $project);

        return back()->with('status', 'Project updated.');
    }

    public function updateMilestone(Request $request, ServiceProject $project, \App\Models\ProjectMilestone $milestone): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:upcoming,in_progress,done'],
            'date_label' => ['nullable', 'string', 'max:100'],
        ]);
        $milestone->update($data);
        AuditLog::record('project_milestone.updated', $milestone);

        return back()->with('status', 'Milestone updated.');
    }
}
