@extends('admin.layout')

@section('content')
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold">Users</h2>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search name/email/organization">
            <select class="rounded border border-slate-300 px-3 py-2" name="role">
                <option value="">All roles</option>
                @foreach (['super-admin', 'admin', 'nurse'] as $roleOpt)
                    <option value="{{ $roleOpt }}" @selected($role === $roleOpt)>{{ $roleOpt }}</option>
                @endforeach
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="name_asc" @selected($sort === 'name_asc')>Name A-Z</option>
                <option value="name_desc" @selected($sort === 'name_desc')>Name Z-A</option>
                <option value="email_asc" @selected($sort === 'email_asc')>Email asc</option>
                <option value="email_desc" @selected($sort === 'email_desc')>Email desc</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.users.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create User</h3>
        <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3">
            @csrf
            <select class="rounded border border-slate-300 px-3 py-2" name="organization_id" required>
                <option value="">Select Organization</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}">{{ $organization->name }} ({{ $organization->code }})</option>
                @endforeach
            </select>
            <input class="rounded border border-slate-300 px-3 py-2" name="name" placeholder="Full Name" required>
            <input class="rounded border border-slate-300 px-3 py-2" type="email" name="email" placeholder="Email" required>
            <input class="rounded border border-slate-300 px-3 py-2" type="password" name="password" placeholder="Password (min 8 chars)" required>
            <select class="rounded border border-slate-300 px-3 py-2" name="role" required>
                @foreach (['super-admin', 'admin', 'nurse'] as $roleOpt)
                    <option value="{{ $roleOpt }}">{{ $roleOpt }}</option>
                @endforeach
            </select>
            <input class="rounded border border-slate-300 px-3 py-2" name="phone" placeholder="Phone">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <div class="md:col-span-4">
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2" type="submit">Create</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">Name</th>
                    <th class="py-2 pr-2">Email</th>
                    <th class="py-2 pr-2">Role</th>
                    <th class="py-2 pr-2">Organization</th>
                    <th class="py-2 pr-2">Status</th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="py-3 pr-2">{{ $user->name }}</td>
                        <td class="py-3 pr-2">{{ $user->email }}</td>
                        <td class="py-3 pr-2">{{ $user->role }}</td>
                        <td class="py-3 pr-2">{{ $user->organization_name ?? '-' }}{{ $user->organization_code ? ' ('.$user->organization_code.')' : '' }}</td>
                        <td class="py-3 pr-2">{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="mt-2 grid gap-2 min-w-[300px]">
                                    @csrf
                                    @method('PUT')
                                    <select class="rounded border border-slate-300 px-2 py-1" name="organization_id" required>
                                        @foreach ($organizations as $organization)
                                            <option value="{{ $organization->id }}" @selected((int) $user->organization_id === (int) $organization->id)>{{ $organization->name }} ({{ $organization->code }})</option>
                                        @endforeach
                                    </select>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="name" value="{{ $user->name }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" type="email" name="email" value="{{ $user->email }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" type="password" name="password" placeholder="Leave blank to keep password">
                                    <select class="rounded border border-slate-300 px-2 py-1" name="role" required>
                                        @foreach (['super-admin', 'admin', 'nurse'] as $roleOpt)
                                            <option value="{{ $roleOpt }}" @selected($user->role === $roleOpt)>{{ $roleOpt }}</option>
                                        @endforeach
                                    </select>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="phone" value="{{ $user->phone }}">
                                    <label class="text-xs flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active)>
                                        Active
                                    </label>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Delete this user?')">
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
            {{ $users->links() }}
        </div>
    </div>
@endsection