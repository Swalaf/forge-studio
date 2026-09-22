<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ServiceRequest;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->string('tab')->value() ?: 'open';
        $requests = ServiceRequest::with('customer')->where('status', $tab)->latest()->paginate(10)->withQueryString();

        $rows = $requests->getCollection()->map(fn ($r) => [
            'title' => $r->contact_name ?? $r->customer?->name ?? 'Guest',
            'meta' => str($r->project_type)->headline().' · '.$r->created_at->format('d M Y'),
            'b' => $r->budget_range ? str($r->budget_range)->headline() : '—',
            'c' => $r->contact_email ?? $r->customer?->email ?? '',
            'status' => str($r->status)->headline(),
            'tone' => match ($r->status) { 'open' => 'wait', 'quoted' => 'accent', 'accepted' => 'ok', 'declined' => 'bad', default => 'info' },
            'primary' => $r->status === 'open' ? ['label' => 'Convert to project', 'url' => route('admin.requests.convert', $r), 'method' => 'POST'] : null,
            'secondary' => $r->status === 'open' ? ['label' => 'Decline', 'url' => route('admin.requests.decline', $r), 'method' => 'POST'] : null,
        ]);

        return view('dashboard.table', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('requests'),
            'crumb' => 'Studio services', 'title' => 'Service pipeline',
            'subtitle' => 'Customization, installation and custom build requests from "Hire us" across the site.',
            'stats' => [
                ['k' => 'Open requests', 'v' => (string) ServiceRequest::where('status', 'open')->count(), 'tone' => 'wait'],
                ['k' => 'Accepted', 'v' => (string) ServiceRequest::where('status', 'accepted')->count(), 'tone' => 'ok'],
                ['k' => 'Declined', 'v' => (string) ServiceRequest::where('status', 'declined')->count()],
            ],
            'tabs' => collect(['open' => 'Open', 'quoted' => 'Quoted', 'accepted' => 'Accepted', 'declined' => 'Declined'])
                ->map(fn ($label, $key) => ['label' => $label, 'active' => $tab === $key])->values(),
            'colA' => 'Contact', 'colB' => 'Budget', 'colC' => 'Email',
            'rows' => $rows, 'pagination' => $requests->links(),
        ]);
    }

    public function convert(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if (! $serviceRequest->customer_id) {
            return back()->withErrors(['convert' => 'Guest requests need a registered customer before they can become a project. Ask them to create an account first.']);
        }

        $project = $serviceRequest->project()->create([
            'customer_id' => $serviceRequest->customer_id,
            'owner_id' => $request->user()->id,
            'title' => str($serviceRequest->project_type)->headline().' for '.$serviceRequest->customer->name,
            'type' => $serviceRequest->project_type,
            'status' => 'active',
        ]);
        $serviceRequest->update(['status' => 'accepted']);
        AuditLog::record('service_request.converted', $serviceRequest, ['project_id' => $project->id]);

        return redirect()->route('admin.projects.show', $project)->with('status', 'Converted to a delivery project.');
    }

    public function decline(ServiceRequest $serviceRequest): RedirectResponse
    {
        $serviceRequest->update(['status' => 'declined']);
        AuditLog::record('service_request.declined', $serviceRequest);

        return back()->with('status', 'Request declined.');
    }
}
