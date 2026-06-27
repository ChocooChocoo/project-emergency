<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConfigController extends Controller
{
    public function edit(): View
    {
        $dssTimeout = (int) (DB::table('system_configurations')
            ->where('scope', 'global')->where('config_key', 'dss_timeout_seconds')
            ->value('config_value') ?: 60);

        return view('admin.config.edit', compact('dssTimeout'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dss_timeout_seconds' => ['required', 'integer', 'min:5', 'max:600'],
        ]);

        // Match keys cover the full unique index (scope, organization_id, config_key).
        DB::table('system_configurations')->updateOrInsert(
            ['scope' => 'global', 'organization_id' => null, 'config_key' => 'dss_timeout_seconds'],
            ['config_value' => (string) $data['dss_timeout_seconds'], 'config_type' => 'int', 'updated_by' => auth()->id()],
        );

        AuditLog::record('config.updated');

        return back()->with('status', 'City settings saved.');
    }
}
