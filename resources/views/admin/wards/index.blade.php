@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Wards</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search ward name or floor">
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="name_asc" @selected($sort === 'name_asc')>Name A-Z</option>
                <option value="name_desc" @selected($sort === 'name_desc')>Name Z-A</option>
                <option value="floor_asc" @selected($sort === 'floor_asc')>Floor asc</option>
                <option value="floor_desc" @selected($sort === 'floor_desc')>Floor desc</option>
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.wards.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Ward</h3>
        <form method="POST" action="{{ route('admin.wards.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
            @csrf
            <input class="rounded border border-slate-300 px-3 py-2" name="name" placeholder="Ward Name" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="floor" placeholder="Floor">
            <div>
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2" type="submit">Create</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">
                        Name
                        <a class="text-xs text-sky-700" href="{{ route('admin.wards.index', array_merge(request()->query(), ['sort' => 'name_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.wards.index', array_merge(request()->query(), ['sort' => 'name_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">
                        Floor
                        <a class="text-xs text-sky-700" href="{{ route('admin.wards.index', array_merge(request()->query(), ['sort' => 'floor_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.wards.index', array_merge(request()->query(), ['sort' => 'floor_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($wards as $ward)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2">{{ $ward->name }}</td>
                        <td class="py-3 pr-2">{{ $ward->floor }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.wards.update', $ward->id) }}" class="mt-2 grid gap-2 min-w-[220px]">
                                    @csrf
                                    @method('PUT')
                                    <input class="rounded border border-slate-300 px-2 py-1" name="name" value="{{ $ward->name }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="floor" value="{{ $ward->floor }}">
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.wards.destroy', $ward->id) }}" onsubmit="return confirm('Delete this ward?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded bg-rose-600 hover:bg-rose-700 text-white px-3 py-1" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $wards->links() }}</div>
    </div>
@endsection
