@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Beds</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search ward, room, or bed">
            <select class="rounded border border-slate-300 px-3 py-2" name="status">
                <option value="">All statuses</option>
                @foreach (['active', 'inactive', 'maintenance'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="ward_asc" @selected($sort === 'ward_asc')>Ward/Room/Bed</option>
                <option value="bed_asc" @selected($sort === 'bed_asc')>Bed asc</option>
                <option value="bed_desc" @selected($sort === 'bed_desc')>Bed desc</option>
                <option value="status_asc" @selected($sort === 'status_asc')>Status A-Z</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.beds.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Bed</h3>
        <form method="POST" action="{{ route('admin.beds.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3">
            @csrf
            <select class="rounded border border-slate-300 px-3 py-2" name="room_id" required>
                <option value="">Select Room</option>
                @foreach($rooms as $room)
                    <option value="{{ $room->id }}">{{ $room->ward_name }} / {{ $room->room_number }}</option>
                @endforeach
            </select>
            <input class="rounded border border-slate-300 px-3 py-2" name="bed_number" placeholder="Bed Number" required>
            <select class="rounded border border-slate-300 px-3 py-2" name="status" required>
                <option value="active">active</option>
                <option value="inactive">inactive</option>
                <option value="maintenance">maintenance</option>
            </select>
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
                        Ward
                        <a class="text-xs text-sky-700" href="{{ route('admin.beds.index', array_merge(request()->query(), ['sort' => 'ward_asc'])) }}">asc</a>
                    </th>
                    <th class="py-2 pr-2">Room</th>
                    <th class="py-2 pr-2">
                        Bed
                        <a class="text-xs text-sky-700" href="{{ route('admin.beds.index', array_merge(request()->query(), ['sort' => 'bed_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.beds.index', array_merge(request()->query(), ['sort' => 'bed_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">
                        Status
                        <a class="text-xs text-sky-700" href="{{ route('admin.beds.index', array_merge(request()->query(), ['sort' => 'status_asc'])) }}">asc</a>
                    </th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($beds as $bed)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2">{{ $bed->ward_name }}</td>
                        <td class="py-3 pr-2">{{ $bed->room_number }}</td>
                        <td class="py-3 pr-2">{{ $bed->bed_number }}</td>
                        <td class="py-3 pr-2">{{ $bed->status }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.beds.update', $bed->id) }}" class="mt-2 grid gap-2 min-w-[230px]">
                                    @csrf
                                    @method('PUT')
                                    <select class="rounded border border-slate-300 px-2 py-1" name="room_id" required>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}" @selected($bed->room_id === $room->id)>
                                                {{ $room->ward_name }} / {{ $room->room_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="bed_number" value="{{ $bed->bed_number }}" required>
                                    <select class="rounded border border-slate-300 px-2 py-1" name="status" required>
                                        @foreach (['active', 'inactive', 'maintenance'] as $opt)
                                            <option value="{{ $opt }}" @selected($bed->status === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.beds.destroy', $bed->id) }}" onsubmit="return confirm('Delete this bed?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded bg-rose-600 hover:bg-rose-700 text-white px-3 py-1" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $beds->links() }}</div>
    </div>
@endsection
