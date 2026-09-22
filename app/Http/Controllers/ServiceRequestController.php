<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function create(): View
    {
        return view('market.hire-us');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_type' => ['required', 'in:customization,installation,custom_build'],
            'budget_range' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:5000'],
            'contact_name' => ['required_without:auth', 'nullable', 'string', 'max:255'],
            'contact_email' => ['required_without:auth', 'nullable', 'email', 'max:255'],
        ]);

        ServiceRequest::create([
            'customer_id' => auth()->id(),
            'contact_name' => auth()->user()->name ?? $data['contact_name'] ?? null,
            'contact_email' => auth()->user()->email ?? $data['contact_email'] ?? null,
            'project_type' => $data['project_type'],
            'budget_range' => $data['budget_range'] ?? null,
            'message' => $data['message'],
        ]);

        return back()->with('status', 'Thanks — the studio will send a quote within 24 hours.');
    }
}
