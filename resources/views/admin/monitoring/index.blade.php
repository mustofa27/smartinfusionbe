@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Monitoring</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold mb-3">Session Filters</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="session_q" value="{{ $sessionQ }}" placeholder="Search patient, MRN, device">
            <select class="rounded border border-slate-300 px-3 py-2" name="session_sort">
                <option value="started_desc" @selected($sessionSort === 'started_desc')>Started newest</option>
                <option value="started_asc" @selected($sessionSort === 'started_asc')>Started oldest</option>
                <option value="remaining_asc" @selected($sessionSort === 'remaining_asc')>Remaining low-high</option>
                <option value="remaining_desc" @selected($sessionSort === 'remaining_desc')>Remaining high-low</option>
            </select>
            <input type="hidden" name="alert_severity" value="{{ $alertSeverity }}">
            <input type="hidden" name="alert_status" value="{{ $alertStatus }}">
            <input type="hidden" name="alert_sort" value="{{ $alertSort }}">
            <div class="flex gap-2 md:col-span-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Apply Session Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.monitoring.index', ['alert_severity' => $alertSeverity, 'alert_status' => $alertStatus, 'alert_sort' => $alertSort]) }}">Reset Session Filter</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <h3 class="font-semibold mb-3">Active Infusion Sessions</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">Session ID</th>
                    <th class="py-2 pr-2">Patient</th>
                    <th class="py-2 pr-2">MRN</th>
                    <th class="py-2 pr-2">Bed</th>
                    <th class="py-2 pr-2">Device</th>
                    <th class="py-2 pr-2">Fluid</th>
                    <th class="py-2 pr-2">
                        Remaining (ml)
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['session_sort' => 'remaining_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['session_sort' => 'remaining_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Flow (ml/h)</th>
                    <th class="py-2 pr-2">
                        Last Reading
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['session_sort' => 'started_desc'])) }}">new</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['session_sort' => 'started_asc'])) }}">old</a>
                    </th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $session)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2">{{ $session->id }}</td>
                        <td class="py-3 pr-2">{{ $session->patient_name ?? '-' }}</td>
                        <td class="py-3 pr-2">{{ $session->patient_mrn ?? '-' }}</td>
                        <td class="py-3 pr-2">{{ $session->ward_name ?? '' }} {{ $session->room_number ?? '' }}-{{ $session->bed_number ?? '-' }}</td>
                        <td class="py-3 pr-2">{{ $session->device_serial ?? '-' }}</td>
                        <td class="py-3 pr-2">{{ $session->fluid_name }}</td>
                        <td class="py-3 pr-2">{{ $session->last_remaining_ml }}</td>
                        <td class="py-3 pr-2">{{ $session->last_flow_ml_per_hour }}</td>
                        <td class="py-3 pr-2">{{ optional($session->last_reading_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                        <td class="py-3 pr-2">
                            <a class="rounded bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs" href="{{ route('admin.monitoring.show', $session->id) }}">Show Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-slate-500" colspan="10">No active sessions.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $sessions->links() }}</div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold mb-3">Alert Filters</h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="hidden" name="session_q" value="{{ $sessionQ }}">
            <input type="hidden" name="session_sort" value="{{ $sessionSort }}">
            <select class="rounded border border-slate-300 px-3 py-2" name="alert_severity">
                <option value="">All severities</option>
                @foreach (['info', 'warning', 'critical'] as $opt)
                    <option value="{{ $opt }}" @selected($alertSeverity === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="alert_status">
                <option value="">Open + acknowledged</option>
                <option value="open" @selected($alertStatus === 'open')>Open only</option>
                <option value="acknowledged" @selected($alertStatus === 'acknowledged')>Acknowledged only</option>
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="alert_sort">
                <option value="triggered_desc" @selected($alertSort === 'triggered_desc')>Triggered newest</option>
                <option value="triggered_asc" @selected($alertSort === 'triggered_asc')>Triggered oldest</option>
                <option value="severity_desc" @selected($alertSort === 'severity_desc')>Severity high-low</option>
                <option value="severity_asc" @selected($alertSort === 'severity_asc')>Severity low-high</option>
            </select>
            <div class="flex gap-2 md:col-span-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Apply Alert Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.monitoring.index', ['session_q' => $sessionQ, 'session_sort' => $sessionSort]) }}">Reset Alert Filter</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <h3 class="font-semibold mb-3">Open Alerts</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">Type</th>
                    <th class="py-2 pr-2">
                        Severity
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['alert_sort' => 'severity_desc'])) }}">critical-first</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['alert_sort' => 'severity_asc'])) }}">info-first</a>
                    </th>
                    <th class="py-2 pr-2">Device</th>
                    <th class="py-2 pr-2">Patient</th>
                    <th class="py-2 pr-2">Status</th>
                    <th class="py-2 pr-2">
                        Triggered
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['alert_sort' => 'triggered_desc'])) }}">new</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.monitoring.index', array_merge(request()->query(), ['alert_sort' => 'triggered_asc'])) }}">old</a>
                    </th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($alerts as $alert)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2">{{ $alert->alert_type }}</td>
                        <td class="py-3 pr-2">{{ $alert->severity }}</td>
                        <td class="py-3 pr-2">{{ $alert->device_serial ?? '-' }}</td>
                        <td class="py-3 pr-2">{{ $alert->patient_name ?? '-' }}</td>
                        <td class="py-3 pr-2">{{ $alert->status }}</td>
                        <td class="py-3 pr-2">{{ optional($alert->triggered_at)->format('Y-m-d H:i:s') }}</td>
                        <td class="py-3 pr-2">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.monitoring.alerts.acknowledge', $alert->id) }}">
                                    @csrf
                                    <button class="rounded bg-sky-600 hover:bg-sky-700 text-white px-3 py-1" type="submit">Acknowledge</button>
                                </form>
                                <form method="POST" action="{{ route('admin.monitoring.alerts.resolve', $alert->id) }}">
                                    @csrf
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1" type="submit">Resolve</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="py-4 text-slate-500" colspan="7">No open alerts.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $alerts->links() }}</div>
    </div>
@endsection
