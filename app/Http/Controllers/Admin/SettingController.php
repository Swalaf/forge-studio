<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Support\Nav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const FIELDS = [
        'site_name' => 'Site name',
        'support_email' => 'Support email',
        'default_commission_pct' => 'Default author commission (%)',
        'payout_threshold_cents' => 'Minimum payout amount (cents)',
    ];

    public function edit(): View
    {
        $values = collect(self::FIELDS)->keys()->mapWithKeys(fn ($key) => [$key => Setting::get($key)]);

        return view('admin.settings', [
            'dashTitle' => 'Forge Admin', 'dashSub' => 'Owner console', 'navGroups' => Nav::admin('settings'),
            'fields' => self::FIELDS, 'values' => $values,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        foreach (array_keys(self::FIELDS) as $key) {
            Setting::put($key, $request->input($key));
        }
        AuditLog::record('settings.updated');

        return back()->with('status', 'Settings saved.');
    }
}
