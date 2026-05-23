@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Devices</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search serial/topic/model">
            <select class="rounded border border-slate-300 px-3 py-2" name="organization_id">
                <option value="">All organizations</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected((string) $organizationId === (string) $organization->id)>{{ $organization->name }} ({{ $organization->code }})</option>
                @endforeach
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="status">
                <option value="">All statuses</option>
                @foreach (['online', 'offline', 'maintenance', 'retired'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="serial_asc" @selected($sort === 'serial_asc')>Serial A-Z</option>
                <option value="serial_desc" @selected($sort === 'serial_desc')>Serial Z-A</option>
                <option value="last_seen_desc" @selected($sort === 'last_seen_desc')>Last seen newest</option>
                <option value="last_seen_asc" @selected($sort === 'last_seen_asc')>Last seen oldest</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.devices.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Device</h3>
        <form method="POST" action="{{ route('admin.devices.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
            @csrf
            <select class="rounded border border-slate-300 px-3 py-2" name="organization_id" required>
                <option value="">Select Organization</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}">{{ $organization->name }} ({{ $organization->code }})</option>
                @endforeach
            </select>
            <input class="rounded border border-slate-300 px-3 py-2" name="serial_number" placeholder="Serial Number" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="mqtt_topic" placeholder="MQTT Topic" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="model" placeholder="Model">
            <input class="rounded border border-slate-300 px-3 py-2" name="firmware_version" placeholder="Firmware Version">
            <select class="rounded border border-slate-300 px-3 py-2" name="status" required>
                <option value="offline">offline</option>
                <option value="online">online</option>
                <option value="maintenance">maintenance</option>
                <option value="retired">retired</option>
            </select>
            <div class="md:col-span-3">
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2" type="submit">Create</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">
                        Serial
                        <a class="text-xs text-sky-700" href="{{ route('admin.devices.index', array_merge(request()->query(), ['sort' => 'serial_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.devices.index', array_merge(request()->query(), ['sort' => 'serial_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Organization</th>
                    <th class="py-2 pr-2">Topic</th>
                    <th class="py-2 pr-2">Model</th>
                    <th class="py-2 pr-2">Firmware</th>
                    <th class="py-2 pr-2">Status</th>
                    <th class="py-2 pr-2">
                        Last Seen
                        <a class="text-xs text-sky-700" href="{{ route('admin.devices.index', array_merge(request()->query(), ['sort' => 'last_seen_desc'])) }}">new</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.devices.index', array_merge(request()->query(), ['sort' => 'last_seen_asc'])) }}">old</a>
                    </th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($devices as $device)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="py-3 pr-2">{{ $device->serial_number }}</td>
                        <td class="py-3 pr-2">{{ $device->organization_name ?? '-' }}{{ $device->organization_code ? ' ('.$device->organization_code.')' : '' }}</td>
                        <td class="py-3 pr-2">{{ $device->mqtt_topic }}</td>
                        <td class="py-3 pr-2">{{ $device->model }}</td>
                        <td class="py-3 pr-2">{{ $device->firmware_version }}</td>
                        <td class="py-3 pr-2">{{ $device->status }}</td>
                        <td class="py-3 pr-2">{{ optional($device->last_seen_at)->format('Y-m-d H:i:s') }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.devices.update', $device->id) }}" class="mt-2 grid gap-2 min-w-[280px]">
                                    @csrf
                                    @method('PUT')
                                    <select class="rounded border border-slate-300 px-2 py-1" name="organization_id" required>
                                        @foreach ($organizations as $organization)
                                            <option value="{{ $organization->id }}" @selected((int) $device->organization_id === (int) $organization->id)>{{ $organization->name }} ({{ $organization->code }})</option>
                                        @endforeach
                                    </select>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="serial_number" value="{{ $device->serial_number }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="mqtt_topic" value="{{ $device->mqtt_topic }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="model" value="{{ $device->model }}">
                                    <input class="rounded border border-slate-300 px-2 py-1" name="firmware_version" value="{{ $device->firmware_version }}">
                                    <select class="rounded border border-slate-300 px-2 py-1" name="status" required>
                                        @foreach (['online', 'offline', 'maintenance', 'retired'] as $opt)
                                            <option value="{{ $opt }}" @selected($device->status === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.devices.destroy', $device->id) }}" onsubmit="return confirm('Delete this device?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded bg-rose-600 hover:bg-rose-700 text-white px-3 py-1" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $devices->links() }}
        </div>
    </div>
@endsection
