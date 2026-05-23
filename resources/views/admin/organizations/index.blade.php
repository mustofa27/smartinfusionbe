@extends('admin.layout')

@section('content')
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold">Organizations</h2>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search name/code/timezone">
            <select class="rounded border border-slate-300 px-3 py-2" name="active">
                <option value="">All statuses</option>
                <option value="1" @selected($active === '1')>Active</option>
                <option value="0" @selected($active === '0')>Inactive</option>
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="name_asc" @selected($sort === 'name_asc')>Name A-Z</option>
                <option value="name_desc" @selected($sort === 'name_desc')>Name Z-A</option>
                <option value="code_asc" @selected($sort === 'code_asc')>Code asc</option>
                <option value="code_desc" @selected($sort === 'code_desc')>Code desc</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.organizations.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Organization</h3>
        <form method="POST" action="{{ route('admin.organizations.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3">
            @csrf
            <input class="rounded border border-slate-300 px-3 py-2" name="name" placeholder="Organization Name" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="code" placeholder="Code" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="timezone" value="Asia/Jakarta" required>
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
                    <th class="py-2 pr-2">Code</th>
                    <th class="py-2 pr-2">Timezone</th>
                    <th class="py-2 pr-2">Status</th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($organizations as $organization)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="py-3 pr-2">{{ $organization->name }}</td>
                        <td class="py-3 pr-2">{{ $organization->code }}</td>
                        <td class="py-3 pr-2">{{ $organization->timezone }}</td>
                        <td class="py-3 pr-2">{{ $organization->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.organizations.update', $organization->id) }}" class="mt-2 grid gap-2 min-w-[280px]">
                                    @csrf
                                    @method('PUT')
                                    <input class="rounded border border-slate-300 px-2 py-1" name="name" value="{{ $organization->name }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="code" value="{{ $organization->code }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="timezone" value="{{ $organization->timezone }}" required>
                                    <label class="text-xs flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($organization->is_active)>
                                        Active
                                    </label>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.organizations.destroy', $organization->id) }}" onsubmit="return confirm('Delete this organization?')">
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
            {{ $organizations->links() }}
        </div>
    </div>
@endsection