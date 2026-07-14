<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\InfusionReading;
use App\Models\InfusionSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $sessionQ = trim((string) $request->query('session_q', ''));
        $alertSeverity = (string) $request->query('alert_severity', '');
        $alertStatus = (string) $request->query('alert_status', '');
        $sessionSort = (string) $request->query('session_sort', 'started_desc');
        $alertSort = (string) $request->query('alert_sort', 'triggered_desc');

        $sessionsQuery = InfusionSession::query()
            ->leftJoin('patients', 'patients.id', '=', 'infusion_sessions.patient_id')
            ->leftJoin('devices', 'devices.id', '=', 'infusion_sessions.device_id')
            ->leftJoin('beds', 'beds.id', '=', 'infusion_sessions.bed_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'beds.room_id')
            ->leftJoin('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('infusion_sessions.organization_id', $orgId)
            ->where('infusion_sessions.status', 'active')
            ->when($sessionQ !== '', function ($query) use ($sessionQ): void {
                $query->where(function ($nested) use ($sessionQ): void {
                    $nested->where('patients.full_name', 'like', "%{$sessionQ}%")
                        ->orWhere('patients.medical_record_no', 'like', "%{$sessionQ}%")
                        ->orWhere('devices.serial_number', 'like', "%{$sessionQ}%");
                });
            })
            ->select([
                'infusion_sessions.*',
                'patients.full_name as patient_name',
                'patients.medical_record_no as patient_mrn',
                'devices.serial_number as device_serial',
                'beds.bed_number',
                'rooms.room_number',
                'wards.name as ward_name',
            ]);

        match ($sessionSort) {
            'started_asc' => $sessionsQuery->orderBy('infusion_sessions.started_at'),
            'remaining_asc' => $sessionsQuery->orderBy('infusion_sessions.last_remaining_ml'),
            'remaining_desc' => $sessionsQuery->orderByDesc('infusion_sessions.last_remaining_ml'),
            default => $sessionsQuery->orderByDesc('infusion_sessions.started_at'),
        };

        $sessions = $sessionsQuery
            ->paginate(10, ['*'], 'sessions_page')
            ->withQueryString();

        $alertsQuery = Alert::query()
            ->leftJoin('devices', 'devices.id', '=', 'alerts.device_id')
            ->leftJoin('patients', 'patients.id', '=', 'alerts.patient_id')
            ->where('alerts.organization_id', $orgId)
            ->whereIn('alerts.status', ['open', 'acknowledged'])
            ->when(in_array($alertSeverity, ['info', 'warning', 'critical'], true), function ($query) use ($alertSeverity): void {
                $query->where('alerts.severity', $alertSeverity);
            })
            ->when(in_array($alertStatus, ['open', 'acknowledged'], true), function ($query) use ($alertStatus): void {
                $query->where('alerts.status', $alertStatus);
            })
            ->orderByDesc('alerts.triggered_at')
            ->select([
                'alerts.*',
                'devices.serial_number as device_serial',
                'patients.full_name as patient_name',
            ]);

        match ($alertSort) {
            'triggered_asc' => $alertsQuery->orderBy('alerts.triggered_at'),
            'severity_desc' => $alertsQuery->orderByRaw("FIELD(alerts.severity, 'critical','warning','info')"),
            'severity_asc' => $alertsQuery->orderByRaw("FIELD(alerts.severity, 'info','warning','critical')"),
            default => $alertsQuery->orderByDesc('alerts.triggered_at'),
        };

        $alerts = $alertsQuery
            ->paginate(10, ['*'], 'alerts_page')
            ->withQueryString();

        return view('admin.monitoring.index', compact('sessions', 'alerts', 'sessionQ', 'alertSeverity', 'alertStatus', 'sessionSort', 'alertSort'));
    }

    public function show(Request $request, int $session): View
    {
        $orgId = (int) $request->user()->organization_id;

        $session = InfusionSession::query()
            ->leftJoin('patients', 'patients.id', '=', 'infusion_sessions.patient_id')
            ->leftJoin('devices', 'devices.id', '=', 'infusion_sessions.device_id')
            ->leftJoin('beds', 'beds.id', '=', 'infusion_sessions.bed_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'beds.room_id')
            ->leftJoin('wards', 'wards.id', '=', 'rooms.ward_id')
            ->leftJoin('users as started_by', 'started_by.id', '=', 'infusion_sessions.started_by_user_id')
            ->leftJoin('users as ended_by', 'ended_by.id', '=', 'infusion_sessions.ended_by_user_id')
            ->where('infusion_sessions.organization_id', $orgId)
            ->where('infusion_sessions.id', $session)
            ->select([
                'infusion_sessions.*',
                'patients.full_name as patient_name',
                'patients.medical_record_no as patient_mrn',
                'devices.serial_number as device_serial',
                'beds.bed_number',
                'rooms.room_number',
                'wards.name as ward_name',
                'started_by.name as started_by_user_name',
                'ended_by.name as ended_by_user_name',
            ])
            ->firstOrFail();

        $readings = InfusionReading::query()
            ->where('infusion_session_id', $session->id)
            ->where('organization_id', $orgId)
            ->orderByDesc('recorded_at')
            ->paginate(20, ['*'], 'readings_page');

        $alerts = Alert::query()
            ->where('infusion_session_id', $session->id)
            ->where('organization_id', $orgId)
            ->orderByDesc('triggered_at')
            ->get();

        return view('admin.monitoring.show', compact('session', 'readings', 'alerts'));
    }

    public function acknowledge(Request $request, int $alert): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Alert::query()->where('organization_id', $orgId)->findOrFail($alert);

        if ($model->status !== 'resolved') {
            $model->status = 'acknowledged';
            $model->acknowledged_at = now();
            $model->acknowledged_by_user_id = $request->user()->id;
            $model->save();
        }

        return back()->with('success', 'Alert acknowledged.');
    }

    public function resolve(Request $request, int $alert): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Alert::query()->where('organization_id', $orgId)->findOrFail($alert);

        if ($model->status !== 'resolved') {
            $model->status = 'resolved';
            $model->resolved_at = now();
            $model->resolved_by_user_id = $request->user()->id;
            $model->save();
        }

        return back()->with('success', 'Alert resolved.');
    }
}