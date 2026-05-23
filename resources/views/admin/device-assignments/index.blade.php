@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Device Assignments</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search device, ward, room, bed">
            <label class="flex items-center gap-2 text-sm rounded border border-slate-300 px-3 py-2">
                <input type="hidden" name="active_only" value="0">
                <input type="checkbox" name="active_only" value="1" @checked($activeOnly === '1')>
                Show active assignments only
            </label>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="mounted_desc" @selected($sort === 'mounted_desc')>Mounted newest</option>
                <option value="mounted_asc" @selected($sort === 'mounted_asc')>Mounted oldest</option>
                <option value="device_asc" @selected($sort === 'device_asc')>Device A-Z</option>
                <option value="ward_asc" @selected($sort === 'ward_asc')>Ward/Room/Bed</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.device-assignments.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Assign Device to Bed</h3>
        <form method="POST" action="{{ route('admin.device-assignments.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
            @csrf
            <select class="rounded border border-slate-300 px-3 py-2" name="device_id" required>
                <option value="">Select Device</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}">{{ $device->serial_number }}</option>
                @endforeach
            </select>

            <select class="rounded border border-slate-300 px-3 py-2" name="bed_id" required>
                <option value="">Select Bed</option>
                @foreach ($beds as $bed)
                    <option value="{{ $bed->id }}">{{ $bed->ward_name }} / {{ $bed->room_number }} / {{ $bed->bed_number }}</option>
                @endforeach
            </select>

            <div>
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2" type="submit">Assign</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">
                        Device
                        <a class="text-xs text-sky-700" href="{{ route('admin.device-assignments.index', array_merge(request()->query(), ['sort' => 'device_asc'])) }}">asc</a>
                    </th>
                    <th class="py-2 pr-2">
                        Ward
                        <a class="text-xs text-sky-700" href="{{ route('admin.device-assignments.index', array_merge(request()->query(), ['sort' => 'ward_asc'])) }}">asc</a>
                    </th>
                    <th class="py-2 pr-2">Room</th>
                    <th class="py-2 pr-2">Bed</th>
                    <th class="py-2 pr-2">
                        Mounted At
                        <a class="text-xs text-sky-700" href="{{ route('admin.device-assignments.index', array_merge(request()->query(), ['sort' => 'mounted_desc'])) }}">new</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.device-assignments.index', array_merge(request()->query(), ['sort' => 'mounted_asc'])) }}">old</a>
                    </th>
                    <th class="py-2 pr-2">Unmounted At</th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignments as $assignment)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2">{{ $assignment->device_serial }}</td>
                        <td class="py-3 pr-2">{{ $assignment->ward_name }}</td>
                        <td class="py-3 pr-2">{{ $assignment->room_number }}</td>
                        <td class="py-3 pr-2">{{ $assignment->bed_number }}</td>
                        <td class="py-3 pr-2">{{ optional($assignment->mounted_at)->format('Y-m-d H:i:s') }}</td>
                        <td class="py-3 pr-2">{{ optional($assignment->unmounted_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                        <td class="py-3 pr-2">
                            @if (!$assignment->unmounted_at)
                                <form method="POST" action="{{ route('admin.device-assignments.unmount', $assignment->id) }}">
                                    @csrf
                                    <button class="rounded bg-amber-600 hover:bg-amber-700 text-white px-3 py-1" type="submit">Unmount</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $assignments->links() }}</div>
    </div>
@endsection
