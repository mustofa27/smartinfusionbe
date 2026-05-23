<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Device;
use App\Models\DeviceBedAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $assignments = DeviceBedAssignment::query()
            ->join('devices', 'devices.id', '=', 'device_bed_assignments.device_id')
            ->join('beds', 'beds.id', '=', 'device_bed_assignments.bed_id')
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('device_bed_assignments.organization_id', $user->organization_id)
            ->select([
                'device_bed_assignments.*',
                'devices.serial_number as device_serial_number',
                'rooms.room_number',
                'beds.bed_number',
                'wards.name as ward_name',
            ])
            ->orderByDesc('device_bed_assignments.mounted_at')
            ->paginate(20);

        return response()->json($assignments);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'device_id' => ['required', 'integer', 'min:1'],
            'bed_id' => ['required', 'integer', 'min:1'],
        ]);

        $device = Device::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($validated['device_id']);

        $bedExists = Bed::query()
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->where('beds.id', $validated['bed_id'])
            ->exists();

        if (! $bedExists) {
            return response()->json([
                'message' => 'Bed not found in your organization.',
            ], 422);
        }

        $deviceHasActive = DeviceBedAssignment::query()
            ->where('organization_id', $user->organization_id)
            ->where('device_id', $device->id)
            ->whereNull('unmounted_at')
            ->exists();

        if ($deviceHasActive) {
            return response()->json([
                'message' => 'Device already has an active bed assignment.',
            ], 422);
        }

        $bedHasActive = DeviceBedAssignment::query()
            ->where('organization_id', $user->organization_id)
            ->where('bed_id', $validated['bed_id'])
            ->whereNull('unmounted_at')
            ->exists();

        if ($bedHasActive) {
            return response()->json([
                'message' => 'Bed already has an active device assignment.',
            ], 422);
        }

        $assignment = DeviceBedAssignment::query()->create([
            'organization_id' => $user->organization_id,
            'device_id' => $device->id,
            'bed_id' => $validated['bed_id'],
            'mounted_at' => now(),
            'mounted_by_user_id' => $user->id,
        ]);

        return response()->json(['data' => $assignment], 201);
    }

    public function unmount(Request $request, int $assignment): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $model = DeviceBedAssignment::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($assignment);

        if (! $model->unmounted_at) {
            $model->unmounted_at = now();
            $model->save();
        }

        return response()->json([
            'message' => 'Assignment unmounted.',
        ]);
    }
}