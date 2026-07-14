<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Organization;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceCrudController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $organizationId = (string) $request->query('organization_id', '');
        $sort = (string) $request->query('sort', 'newest');

        $devicesQuery = Device::query()
            ->leftJoin('organizations', 'organizations.id', '=', 'devices.organization_id')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('devices.serial_number', 'like', "%{$q}%")
                        ->orWhere('devices.mqtt_topic', 'like', "%{$q}%")
                        ->orWhere('devices.model', 'like', "%{$q}%")
                        ->orWhere('organizations.name', 'like', "%{$q}%")
                        ->orWhere('organizations.code', 'like', "%{$q}%");
                });
            })
            ->when(in_array($status, ['online', 'offline', 'maintenance', 'retired'], true), function ($query) use ($status): void {
                $query->where('devices.status', $status);
            })
            ->when(ctype_digit($organizationId), function ($query) use ($organizationId): void {
                $query->where('devices.organization_id', (int) $organizationId);
            })
            ->select(['devices.*', 'organizations.name as organization_name', 'organizations.code as organization_code']);

        match ($sort) {
            'serial_asc' => $devicesQuery->orderBy('devices.serial_number'),
            'serial_desc' => $devicesQuery->orderByDesc('devices.serial_number'),
            'last_seen_desc' => $devicesQuery->orderByDesc('devices.last_seen_at'),
            'last_seen_asc' => $devicesQuery->orderBy('devices.last_seen_at'),
            default => $devicesQuery->orderByDesc('devices.id'),
        };

        $devices = $devicesQuery
            ->paginate(15)
            ->withQueryString();

        $organizations = Organization::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.devices.index', compact('devices', 'organizations', 'q', 'status', 'organizationId', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'serial_number' => [
                'required', 'string', 'max:120',
                Rule::unique('devices')->where(fn ($q) => $q->where('organization_id', (int) $request->input('organization_id'))),
            ],
            'mqtt_topic' => ['required', 'string', 'max:255', 'unique:devices,mqtt_topic'],
            'model' => ['nullable', 'string', 'max:80'],
            'firmware_version' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['online', 'offline', 'maintenance', 'retired'])],
        ]);

        Device::query()->create($validated);

        return back()->with('success', 'Device created.');
    }

    public function update(Request $request, int $device): RedirectResponse
    {
        $model = Device::query()->findOrFail($device);

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'serial_number' => [
                'required', 'string', 'max:120',
                Rule::unique('devices')
                    ->where(fn ($q) => $q->where('organization_id', (int) $request->input('organization_id')))
                    ->ignore($model->id),
            ],
            'mqtt_topic' => ['required', 'string', 'max:255', Rule::unique('devices', 'mqtt_topic')->ignore($model->id)],
            'model' => ['nullable', 'string', 'max:80'],
            'firmware_version' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['online', 'offline', 'maintenance', 'retired'])],
        ]);

        $model->fill($validated)->save();

        return back()->with('success', 'Device updated.');
    }

    public function destroy(Request $request, int $device): RedirectResponse
    {
        $model = Device::query()->findOrFail($device);
        $model->delete();

        return back()->with('success', 'Device deleted.');
    }

    public function showQrCode(int $device): View
    {
        $device = Device::query()->findOrFail($device);

        $qrCodeData = sprintf('smartinfus://device/%s', $device->serial_number);

        $qrCode = new QrCode($qrCodeData);
        $writer = new SvgWriter;
        $result = $writer->write($qrCode);
        $qrSvg = $result->getString();

        return view('admin.devices.qr-code', compact('device', 'qrSvg', 'qrCodeData'));
    }

    public function printAllQrCodes(): View
    {
        $devices = Device::query()
            ->orderBy('serial_number')
            ->get(['id', 'serial_number', 'model']);

        $qrCodes = [];

        $writer = new SvgWriter;

        foreach ($devices as $device) {
            $qrCodeData = sprintf('smartinfus://device/%s', $device->serial_number);

            $qrCode = new QrCode(
                data: $qrCodeData,
                size: 200,
                margin: 5,
            );

            $result = $writer->write($qrCode);

            $qrCodes[] = [
                'serial_number' => $device->serial_number,
                'model' => $device->model,
                'svg' => $result->getString(),
            ];
        }

        return view('admin.devices.qr-codes-print', compact('qrCodes'));
    }
}
