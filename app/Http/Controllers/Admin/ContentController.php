<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    private const FIELDS = [
        'home_headline' => 'Homepage headline',
        'home_subhead' => 'Homepage subheading',
        'studio_bio' => 'Forge Studio bio',
        'hire_us_headline' => '"Hire the studio" headline',
    ];

    public function edit(): View
    {
        $values = collect(self::FIELDS)->keys()->mapWithKeys(fn ($key) => [$key => Setting::get($key)]);

        return view('admin.content', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('content'),
            'fields' => self::FIELDS, 'values' => $values,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        foreach (array_keys(self::FIELDS) as $key) {
            Setting::put($key, $request->input($key), 'content');
        }
        AuditLog::record('content.updated');

        return back()->with('status', 'Content saved.');
    }
}
