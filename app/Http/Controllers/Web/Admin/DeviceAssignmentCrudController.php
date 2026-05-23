<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Device;
use App\Models\DeviceBedAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceAssignmentCrudController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $q = trim((string) $request->query('q', ''));
        $activeOnly = (string) $request->query('active_only', '1');
        $sort = (string) $request->query('sort', 'mounted_desc');

        $devices = Device::query()
            ->where('organization_id', $orgId)
            ->whereNot('status', 'retired')
            ->orderBy('serial_number')
            ->get(['id', 'serial_number']);

        $beds = Bed::query()
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->where('beds.status', 'active')
            ->orderBy('wards.name')
            ->orderBy('rooms.room_number')
            ->orderBy('beds.bed_number')
            ->select([
                'beds.id',
                'beds.bed_number',
                'rooms.room_number',
                'wards.name as ward_name',
            ])
            ->get();

        $assignmentsQuery = DeviceBedAssignment::query()
            ->join('devices', 'devices.id', '=', 'device_bed_assignments.device_id')
            ->join('beds', 'beds.id', '=', 'device_bed_assignments.bed_id')
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('device_bed_assignments.organization_id', $orgId)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('devices.serial_number', 'like', "%{$q}%")
                        ->orWhere('wards.name', 'like', "%{$q}%")
                        ->orWhere('rooms.room_number', 'like', "%{$q}%")
                        ->orWhere('beds.bed_number', 'like', "%{$q}%");
                });
            })
            ->when($activeOnly === '1', function ($query): void {
                $query->whereNull('device_bed_assignments.unmounted_at');
            })
            ->select([
                'device_bed_assignments.*',
                'devices.serial_number as device_serial',
                'wards.name as ward_name',
                'rooms.room_number',
                'beds.bed_number',
            ]);

        match ($sort) {
            'mounted_asc' => $assignmentsQuery->orderBy('device_bed_assignments.mounted_at'),
            'device_asc' => $assignmentsQuery->orderBy('devices.serial_number'),
            'ward_asc' => $assignmentsQuery->orderBy('wards.name')->orderBy('rooms.room_number')->orderBy('beds.bed_number'),
            default => $assignmentsQuery->orderByDesc('device_bed_assignments.mounted_at'),
        };

        $assignments = $assignmentsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.device-assignments.index', compact('devices', 'beds', 'assignments', 'q', 'activeOnly', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'device_id' => ['required', 'integer', 'min:1'],
            'bed_id' => ['required', 'integer', 'min:1'],
        ]);

        $device = Device::query()->where('organization_id', $orgId)->findOrFail($validated['device_id']);

        $bedExists = Bed::query()
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->where('beds.id', $validated['bed_id'])
            ->exists();

        if (! $bedExists) {
            return back()->withErrors(['bed_id' => 'Bed not found in your organization.'])->withInput();
        }

        $deviceHasActive = DeviceBedAssignment::query()
            ->where('organization_id', $orgId)
            ->where('device_id', $device->id)
            ->whereNull('unmounted_at')
            ->exists();

        if ($deviceHasActive) {
            return back()->withErrors(['device_id' => 'Device already has an active bed assignment.'])->withInput();
        }

        $bedHasActive = DeviceBedAssignment::query()
            ->where('organization_id', $orgId)
            ->where('bed_id', $validated['bed_id'])
            ->whereNull('unmounted_at')
            ->exists();

        if ($bedHasActive) {
            return back()->withErrors(['bed_id' => 'Bed already has an active device assignment.'])->withInput();
        }

        DeviceBedAssignment::query()->create([
            'organization_id' => $orgId,
            'device_id' => $device->id,
            'bed_id' => $validated['bed_id'],
            'mounted_at' => now(),
            'mounted_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Device assigned to bed.');
    }

    public function unmount(Request $request, int $assignment): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = DeviceBedAssignment::query()
            ->where('organization_id', $orgId)
            ->findOrFail($assignment);

        if (! $model->unmounted_at) {
            $model->unmounted_at = now();
            $model->save();
        }

        return back()->with('success', 'Assignment unmounted.');
    }
}
