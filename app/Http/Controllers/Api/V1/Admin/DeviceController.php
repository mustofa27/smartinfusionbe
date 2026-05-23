<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = Device::query()
            ->where('organization_id', $user->organization_id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($devices);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'serial_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique('devices')->where(fn ($q) => $q->where('organization_id', $user->organization_id)),
            ],
            'mqtt_topic' => ['required', 'string', 'max:255', 'unique:devices,mqtt_topic'],
            'model' => ['nullable', 'string', 'max:80'],
            'firmware_version' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['online', 'offline', 'maintenance', 'retired'])],
            'metadata' => ['nullable', 'array'],
        ]);

        $device = Device::query()->create([
            ...$validated,
            'organization_id' => $user->organization_id,
            'status' => $validated['status'] ?? 'offline',
        ]);

        return response()->json(['data' => $device], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $device = Device::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        return response()->json(['data' => $device]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $device = Device::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'serial_number' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('devices')
                    ->where(fn ($q) => $q->where('organization_id', $user->organization_id))
                    ->ignore($device->id),
            ],
            'mqtt_topic' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('devices', 'mqtt_topic')->ignore($device->id)],
            'model' => ['sometimes', 'nullable', 'string', 'max:80'],
            'firmware_version' => ['sometimes', 'nullable', 'string', 'max:80'],
            'status' => ['sometimes', Rule::in(['online', 'offline', 'maintenance', 'retired'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);

        $device->fill($validated);
        $device->save();

        return response()->json(['data' => $device]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $device = Device::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        $device->delete();

        return response()->json(['message' => 'Device deleted.']);
    }
}
