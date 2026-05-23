<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-slate-900 border border-slate-700 p-8 shadow-2xl">
        <h1 class="text-2xl font-semibold">Smartinfus Admin</h1>
        <p class="text-slate-300 mt-1">Sign in with organization code</p>

        @if ($errors->any())
            <div class="mt-4 rounded border border-rose-300 bg-rose-100/10 px-3 py-2 text-rose-300 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.submit') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm mb-1">Organization Code</label>
                <input class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" name="organization_code" value="{{ old('organization_code') }}" required>
            </div>
            <div>
                <label class="block text-sm mb-1">Email</label>
                <input class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="block text-sm mb-1">Password</label>
                <input class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" type="password" name="password" required>
            </div>

            <button class="w-full rounded bg-emerald-600 hover:bg-emerald-700 px-4 py-2 font-medium" type="submit">Login</button>
        </form>
    </div>
</body>
</html>
