@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Dashboard</h2>
    <p class="text-slate-600 mt-1">Overview of your infusion monitoring operations.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
        <div class="rounded-xl bg-white border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Patients</p>
            <p class="text-3xl font-semibold mt-1">{{ $stats['patients'] }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Devices</p>
            <p class="text-3xl font-semibold mt-1">{{ $stats['devices'] }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Active Sessions</p>
            <p class="text-3xl font-semibold mt-1">{{ $stats['active_sessions'] }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Open Alerts</p>
            <p class="text-3xl font-semibold mt-1">{{ $stats['open_alerts'] }}</p>
        </div>
    </div>

    @if (! empty($superAdminStats))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div class="rounded-xl bg-white border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Total Organizations</p>
                <p class="text-3xl font-semibold mt-1">{{ $superAdminStats['organizations'] }}</p>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 p-5">
                <p class="text-sm text-slate-500">Total Users</p>
                <p class="text-3xl font-semibold mt-1">{{ $superAdminStats['users'] }}</p>
            </div>
        </div>
    @endif
@endsection
