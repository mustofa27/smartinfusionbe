@extends('admin.layout')

@section('content')
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold">Session Detail #{{ $session->id }}</h2>
        <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2 text-sm" href="{{ route('admin.monitoring.index') }}">Back to Monitoring</a>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold mb-4">Session Information</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-slate-500">Session ID</dt>
                <dd class="font-medium">{{ $session->id }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Status</dt>
                <dd class="font-medium">
                    <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold
                        @if ($session->status === 'active') bg-emerald-100 text-emerald-700
                        @elseif ($session->status === 'paused') bg-amber-100 text-amber-700
                        @elseif ($session->status === 'completed') bg-blue-100 text-blue-700
                        @else bg-rose-100 text-rose-700 @endif">
                        {{ $session->status }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">Patient Name</dt>
                <dd class="font-medium">{{ $session->patient_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">MRN</dt>
                <dd class="font-medium">{{ $session->patient_mrn ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Bed Location</dt>
                <dd class="font-medium">{{ $session->ward_name ?? '' }} {{ $session->room_number ?? '' }}-{{ $session->bed_number ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Device Serial</dt>
                <dd class="font-medium">{{ $session->device_serial ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Fluid Name</dt>
                <dd class="font-medium">{{ $session->fluid_name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Bag Volume</dt>
                <dd class="font-medium">{{ $session->bag_volume_ml }} ml</dd>
            </div>
            <div>
                <dt class="text-slate-500">Started At</dt>
                <dd class="font-medium">{{ optional($session->started_at)->format('Y-m-d H:i:s') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Ended At</dt>
                <dd class="font-medium">{{ optional($session->ended_at)->format('Y-m-d H:i:s') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Started By</dt>
                <dd class="font-medium">{{ $session->started_by_user_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Ended By</dt>
                <dd class="font-medium">{{ $session->ended_by_user_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Last Remaining</dt>
                <dd class="font-medium">{{ $session->last_remaining_ml ?? '-' }} ml</dd>
            </div>
            <div>
                <dt class="text-slate-500">Last Flow Rate</dt>
                <dd class="font-medium">{{ $session->last_flow_ml_per_hour ?? '-' }} ml/h</dd>
            </div>
            <div>
                <dt class="text-slate-500">Last Reading At</dt>
                <dd class="font-medium">{{ optional($session->last_reading_at)->format('Y-m-d H:i:s') ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <h3 class="font-semibold mb-3">Infusion Readings</h3>
        @if ($readings->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-slate-200">
                        <th class="py-2 pr-2">#</th>
                        <th class="py-2 pr-2">Recorded At</th>
                        <th class="py-2 pr-2">Weight (g)</th>
                        <th class="py-2 pr-2">Remaining (ml)</th>
                        <th class="py-2 pr-2">Flow (ml/h)</th>
                        <th class="py-2 pr-2">Battery (%)</th>
                        <th class="py-2 pr-2">Signal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($readings as $reading)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-2">{{ $reading->id }}</td>
                            <td class="py-3 pr-2">{{ optional($reading->recorded_at)->format('Y-m-d H:i:s') }}</td>
                            <td class="py-3 pr-2">{{ $reading->measured_weight_grams }}</td>
                            <td class="py-3 pr-2">{{ $reading->remaining_ml }}</td>
                            <td class="py-3 pr-2">{{ $reading->flow_ml_per_hour ?? '-' }}</td>
                            <td class="py-3 pr-2">{{ $reading->battery_percent ?? '-' }}</td>
                            <td class="py-3 pr-2">{{ $reading->signal_quality ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $readings->links() }}</div>
        @else
            <p class="text-slate-500">No readings recorded yet.</p>
        @endif
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <h3 class="font-semibold mb-3">Alerts</h3>
        @if ($alerts->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b border-slate-200">
                        <th class="py-2 pr-2">Type</th>
                        <th class="py-2 pr-2">Severity</th>
                        <th class="py-2 pr-2">Message</th>
                        <th class="py-2 pr-2">Status</th>
                        <th class="py-2 pr-2">Triggered At</th>
                        <th class="py-2 pr-2">Acknowledged At</th>
                        <th class="py-2 pr-2">Resolved At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alerts as $alert)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-2">{{ $alert->alert_type }}</td>
                            <td class="py-3 pr-2">
                                <span class="inline-block rounded px-2 py-0.5 text-xs font-semibold
                                    @if ($alert->severity === 'critical') bg-rose-100 text-rose-700
                                    @elseif ($alert->severity === 'warning') bg-amber-100 text-amber-700
                                    @else bg-sky-100 text-sky-700 @endif">
                                    {{ $alert->severity }}
                                </span>
                            </td>
                            <td class="py-3 pr-2">{{ $alert->message }}</td>
                            <td class="py-3 pr-2">{{ $alert->status }}</td>
                            <td class="py-3 pr-2">{{ optional($alert->triggered_at)->format('Y-m-d H:i:s') }}</td>
                            <td class="py-3 pr-2">{{ optional($alert->acknowledged_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td class="py-3 pr-2">{{ optional($alert->resolved_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-slate-500">No alerts for this session.</p>
        @endif
    </div>
@endsection