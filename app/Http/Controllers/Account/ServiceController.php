<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ServiceProject;
use App\Support\Nav;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('account.services', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('services'),
            'projects' => Auth::user()->serviceProjects()->with('owner', 'milestones')->latest()->get(),
        ]);
    }

    public function show(ServiceProject $project): View
    {
        $this->authorize('view', $project);

        return view('account.service-show', [
            'dashTitle' => 'Forge Market', 'dashSub' => 'Customer account', 'navGroups' => Nav::account('services'),
            'project' => $project->load('owner', 'milestones'),
        ]);
    }
}
