<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Device;
use App\Models\InfusionSession;
use App\Models\NurseDeviceSubscription;
use App\Models\NurseFcmToken;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NurseMonitoringController extends Controller
{
    public function monitorByDeviceCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:120'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $device = Device::query()
            ->where('organization_id', $user->organization_id)
            ->where('serial_number', $validated['device_code'])
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not found for this organization.',
            ], 404);
        }

        NurseDeviceSubscription::query()->firstOrCreate([
            'organization_id' => $user->organization_id,
            'nurse_user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        $session = InfusionSession::query()
            ->where('organization_id', $user->organization_id)
            ->where('device_id', $device->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        if (! $session) {
            return response()->json([
                'session_required' => true,
                'device' => [
                    'id' => $device->id,
                    'serial_number' => $device->serial_number,
                    'status' => $device->status,
                ],
                'required_fields' => [
                    'patient_id',
                    'bed_id',
                    'fluid_name',
                    'bag_volume_ml',
                    'bag_empty_weight_grams',
                    'initial_weight_grams',
                    'started_at',
                ],
            ]);
        }

        return response()->json([
            'session_required' => false,
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->serial_number,
                'status' => $device->status,
            ],
            'session' => [
                'id' => $session->id,
                'patient_id' => $session->patient_id,
                'bed_id' => $session->bed_id,
                'fluid_name' => $session->fluid_name,
                'started_at' => $session->started_at,
                'last_remaining_ml' => $session->last_remaining_ml,
                'last_flow_ml_per_hour' => $session->last_flow_ml_per_hour,
                'last_reading_at' => $session->last_reading_at,
            ],
        ]);
    }

    public function startInfusionSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:120'],
            'patient_id' => ['required', 'integer', 'min:1'],
            'bed_id' => ['required', 'integer', 'min:1'],
            'fluid_name' => ['required', 'string', 'max:120'],
            'bag_volume_ml' => ['required', 'numeric', 'gt:0'],
            'bag_empty_weight_grams' => ['required', 'numeric', 'gte:0'],
            'initial_weight_grams' => ['required', 'numeric', 'gt:0'],
            'fluid_density_g_per_ml' => ['nullable', 'numeric', 'gt:0'],
            'started_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $device = Device::query()
            ->where('organization_id', $user->organization_id)
            ->where('serial_number', $validated['device_code'])
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not found for this organization.',
            ], 404);
        }

        $patient = Patient::query()
            ->where('organization_id', $user->organization_id)
            ->whereKey($validated['patient_id'])
            ->first();

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found for this organization.',
            ], 422);
        }

        $bedExistsInOrg = DB::table('beds')
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('beds.id', $validated['bed_id'])
            ->where('wards.organization_id', $user->organization_id)
            ->exists();

        if (! $bedExistsInOrg) {
            return response()->json([
                'message' => 'Bed not found for this organization.',
            ], 422);
        }

        $existingDeviceSession = InfusionSession::query()
            ->where('organization_id', $user->organization_id)
            ->where('device_id', $device->id)
            ->where('status', 'active')
            ->exists();

        if ($existingDeviceSession) {
            return response()->json([
                'message' => 'Device already has an active infusion session.',
            ], 409);
        }

        $existingPatientSession = InfusionSession::query()
            ->where('organization_id', $user->organization_id)
            ->where('patient_id', $patient->id)
            ->where('status', 'active')
            ->exists();

        if ($existingPatientSession) {
            return response()->json([
                'message' => 'Patient already has an active infusion session.',
            ], 409);
        }

        $bedLocation = DB::table('beds')
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('beds.id', $validated['bed_id'])
            ->first([
                'wards.name as ward_name',
                'rooms.room_number',
                'beds.bed_number',
            ]);

        $bedLabel = null;
        if ($bedLocation) {
            $bedLabel = sprintf(
                '%s / %s / %s',
                $bedLocation->ward_name,
                $bedLocation->room_number,
                $bedLocation->bed_number,
            );
        }

        $session = InfusionSession::query()->create([
            'organization_id' => $user->organization_id,
            'patient_id' => $patient->id,
            'device_id' => $device->id,
            'bed_id' => (int) $validated['bed_id'],
            'started_by_user_id' => $user->id,
            'fluid_name' => $validated['fluid_name'],
            'bag_volume_ml' => $validated['bag_volume_ml'],
            'bag_empty_weight_grams' => $validated['bag_empty_weight_grams'],
            'initial_weight_grams' => $validated['initial_weight_grams'],
            'fluid_density_g_per_ml' => $validated['fluid_density_g_per_ml'] ?? 1,
            'started_at' => $validated['started_at'] ?? now(),
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
            'patient_name_snapshot' => $patient->full_name,
            'mrn_snapshot' => $patient->medical_record_no,
            'bed_label_snapshot' => $bedLabel,
        ]);

        NurseDeviceSubscription::query()->firstOrCreate([
            'organization_id' => $user->organization_id,
            'nurse_user_id' => $user->id,
            'device_id' => $device->id,
        ]);

        return response()->json([
            'message' => 'Infusion session started.',
            'session' => [
                'id' => $session->id,
                'device_id' => $session->device_id,
                'patient_id' => $session->patient_id,
                'status' => $session->status,
                'started_at' => $session->started_at,
            ],
        ], 201);
    }

    public function activeSessions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $sessions = InfusionSession::query()
            ->join('nurse_device_subscriptions as nds', function ($join) use ($user): void {
                $join->on('nds.device_id', '=', 'infusion_sessions.device_id')
                    ->where('nds.nurse_user_id', '=', $user->id)
                    ->where('nds.organization_id', '=', $user->organization_id);
            })
            ->where('infusion_sessions.organization_id', $user->organization_id)
            ->where('infusion_sessions.status', 'active')
            ->orderByDesc('infusion_sessions.started_at')
            ->limit(100)
            ->get([
                'infusion_sessions.id',
                'infusion_sessions.device_id',
                'infusion_sessions.patient_id',
                'infusion_sessions.fluid_name',
                'infusion_sessions.started_at',
                'infusion_sessions.last_remaining_ml',
                'infusion_sessions.last_flow_ml_per_hour',
                'infusion_sessions.last_reading_at',
            ]);

        return response()->json([
            'data' => $sessions,
        ]);
    }

    public function pauseInfusionSession(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $session = $this->findSessionInNurseScope($user, $sessionId);
        if (! $session) {
            return response()->json([
                'message' => 'Infusion session not found for this nurse scope.',
            ], 404);
        }

        if ($session->status !== 'active') {
            return response()->json([
                'message' => 'Only active infusion sessions can be paused.',
            ], 409);
        }

        $session->status = 'paused';
        if (array_key_exists('notes', $validated)) {
            $session->notes = $validated['notes'];
        }
        $session->save();

        return response()->json([
            'message' => 'Infusion session paused.',
            'data' => [
                'id' => $session->id,
                'status' => $session->status,
            ],
        ]);
    }

    public function completeInfusionSession(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'ended_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $session = $this->findSessionInNurseScope($user, $sessionId);
        if (! $session) {
            return response()->json([
                'message' => 'Infusion session not found for this nurse scope.',
            ], 404);
        }

        if (! in_array($session->status, ['active', 'paused'], true)) {
            return response()->json([
                'message' => 'Only active or paused infusion sessions can be completed.',
            ], 409);
        }

        $session->status = 'completed';
        $session->ended_at = $validated['ended_at'] ?? now();
        $session->ended_by_user_id = $user->id;
        if (array_key_exists('notes', $validated)) {
            $session->notes = $validated['notes'];
        }
        $session->save();

        return response()->json([
            'message' => 'Infusion session completed.',
            'data' => [
                'id' => $session->id,
                'status' => $session->status,
                'ended_at' => $session->ended_at,
            ],
        ]);
    }

    public function interruptInfusionSession(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'ended_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $session = $this->findSessionInNurseScope($user, $sessionId);
        if (! $session) {
            return response()->json([
                'message' => 'Infusion session not found for this nurse scope.',
            ], 404);
        }

        if (! in_array($session->status, ['active', 'paused'], true)) {
            return response()->json([
                'message' => 'Only active or paused infusion sessions can be interrupted.',
            ], 409);
        }

        $session->status = 'interrupted';
        $session->ended_at = $validated['ended_at'] ?? now();
        $session->ended_by_user_id = $user->id;
        if (array_key_exists('notes', $validated)) {
            $session->notes = $validated['notes'];
        }
        $session->save();

        return response()->json([
            'message' => 'Infusion session interrupted.',
            'data' => [
                'id' => $session->id,
                'status' => $session->status,
                'ended_at' => $session->ended_at,
            ],
        ]);
    }

    public function registerFcmToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'device_os' => ['nullable', 'string', 'max:20'],
            'device_model' => ['nullable', 'string', 'max:80'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $token = NurseFcmToken::query()->updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            [
                'organization_id' => $user->organization_id,
                'nurse_user_id' => $user->id,
                'app_version' => $validated['app_version'] ?? null,
                'device_os' => $validated['device_os'] ?? null,
                'device_model' => $validated['device_model'] ?? null,
                'last_seen_at' => now(),
                'is_active' => true,
            ],
        );

        return response()->json([
            'message' => 'FCM token registered.',
            'data' => [
                'id' => $token->id,
                'is_active' => $token->is_active,
                'last_seen_at' => $token->last_seen_at,
            ],
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $status = $request->query('status');

        $query = Alert::query()
            ->join('nurse_device_subscriptions as nds', function ($join) use ($user): void {
                $join->on('nds.device_id', '=', 'alerts.device_id')
                    ->where('nds.nurse_user_id', '=', $user->id)
                    ->where('nds.organization_id', '=', $user->organization_id);
            })
            ->where('alerts.organization_id', $user->organization_id)
            ->orderByDesc('alerts.triggered_at');

        if (is_string($status) && in_array($status, ['open', 'acknowledged', 'resolved'], true)) {
            $query->where('alerts.status', $status);
        }

        $alerts = $query->limit(100)->get([
            'alerts.id',
            'alerts.device_id',
            'alerts.patient_id',
            'alerts.infusion_session_id',
            'alerts.alert_type',
            'alerts.severity',
            'alerts.message',
            'alerts.triggered_at',
            'alerts.status',
            'alerts.acknowledged_at',
            'alerts.resolved_at',
        ]);

        return response()->json([
            'data' => $alerts,
        ]);
    }

    public function acknowledgeAlert(Request $request, int $alertId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->assertNurse($user);

        $alert = Alert::query()
            ->join('nurse_device_subscriptions as nds', function ($join) use ($user): void {
                $join->on('nds.device_id', '=', 'alerts.device_id')
                    ->where('nds.nurse_user_id', '=', $user->id)
                    ->where('nds.organization_id', '=', $user->organization_id);
            })
            ->where('alerts.organization_id', $user->organization_id)
            ->where('alerts.id', $alertId)
            ->first(['alerts.*']);

        if (! $alert) {
            return response()->json([
                'message' => 'Alert not found for this nurse scope.',
            ], 404);
        }

        if ($alert->status === 'resolved') {
            return response()->json([
                'message' => 'Alert already resolved.',
            ], 409);
        }

        if ($alert->status !== 'acknowledged') {
            $alert->status = 'acknowledged';
            $alert->acknowledged_at = now();
            $alert->acknowledged_by_user_id = $user->id;
            $alert->save();
        }

        return response()->json([
            'message' => 'Alert acknowledged.',
            'data' => [
                'id' => $alert->id,
                'status' => $alert->status,
                'acknowledged_at' => $alert->acknowledged_at,
            ],
        ]);
    }

    private function assertNurse(User $user): void
    {
        abort_if($user->role !== 'nurse', 403, 'Nurse role required.');
    }

    private function findSessionInNurseScope(User $user, int $sessionId): ?InfusionSession
    {
        return InfusionSession::query()
            ->join('nurse_device_subscriptions as nds', function ($join) use ($user): void {
                $join->on('nds.device_id', '=', 'infusion_sessions.device_id')
                    ->where('nds.nurse_user_id', '=', $user->id)
                    ->where('nds.organization_id', '=', $user->organization_id);
            })
            ->where('infusion_sessions.organization_id', $user->organization_id)
            ->where('infusion_sessions.id', $sessionId)
            ->first(['infusion_sessions.*']);
    }
}
