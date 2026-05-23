<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Device;
use App\Models\InfusionSession;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $orgId = (int) $request->user()->organization_id;

        $stats = [
            'patients' => Patient::query()->where('organization_id', $orgId)->count(),
            'devices' => Device::query()->where('organization_id', $orgId)->count(),
            'active_sessions' => InfusionSession::query()->where('organization_id', $orgId)->where('status', 'active')->count(),
            'open_alerts' => Alert::query()->where('organization_id', $orgId)->whereIn('status', ['open', 'acknowledged'])->count(),
        ];

        $superAdminStats = null;
        if (($user->role ?? null) === 'super-admin') {
            $superAdminStats = [
                'organizations' => Organization::query()->count(),
                'users' => User::query()->count(),
            ];
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'superAdminStats' => $superAdminStats,
        ]);
    }
}
