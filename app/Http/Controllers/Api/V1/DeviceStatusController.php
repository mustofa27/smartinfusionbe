<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\InfusionSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceStatusController extends Controller
{
    public function monitoredStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:120'],
        ]);

        $device = Device::query()
            ->where('serial_number', $validated['device_code'])
            ->first();

        if (! $device) {
            return response()->json([
                'monitored' => false,
                'message' => 'Device not found.',
            ], 404);
        }

        $activeSession = InfusionSession::query()
            ->where('device_id', $device->id)
            ->where('status', 'active')
            ->exists();

        return response()->json([
            'monitored' => $activeSession,
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->serial_number,
                'status' => $device->status,
            ],
        ]);
    }
}