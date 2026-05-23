<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Smartinfus Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen grid grid-cols-1 md:grid-cols-[260px_1fr]">
        <aside class="bg-slate-900 text-slate-100 p-6">
            <h1 class="text-xl font-semibold">Smartinfus Admin</h1>
            <p class="text-sm text-slate-300 mt-1">{{ auth()->user()->name ?? 'Guest' }}</p>

            <nav class="mt-8 space-y-2 text-sm">
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.patients.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.patients.index') }}">Patients</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.device-assignments.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.device-assignments.index') }}">Device Assignments</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.alert-rules.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.alert-rules.index') }}">Alert Rules</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.monitoring.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.monitoring.index') }}">Monitoring</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.wards.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.wards.index') }}">Wards</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.rooms.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.rooms.index') }}">Rooms</a>
                <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.beds.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.beds.index') }}">Beds</a>

                @if ((auth()->user()->role ?? null) === 'super-admin')
                    <div class="mt-4 pt-4 border-t border-slate-700/70">
                        <p class="px-3 py-1 text-xs uppercase tracking-wide text-slate-400">Superadmin</p>
                        <a class="mt-1 block px-3 py-2 rounded {{ request()->routeIs('admin.organizations.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.organizations.index') }}">Organizations</a>
                        <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.users.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.users.index') }}">Users</a>
                        <a class="block px-3 py-2 rounded {{ request()->routeIs('admin.devices.*') ? 'bg-emerald-600' : 'hover:bg-slate-800' }}" href="{{ route('admin.devices.index') }}">Devices</a>
                    </div>
                @endif
            </nav>

            <form action="{{ route('admin.logout') }}" method="POST" class="mt-10">
                @csrf
                <button class="w-full px-3 py-2 rounded bg-rose-600 hover:bg-rose-700 text-white text-sm" type="submit">Logout</button>
            </form>
        </aside>

        <main class="p-6 md:p-10">
            @if (session('success'))
                <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3">
                    <p class="font-medium mb-2">Please fix the following:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
