@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Rooms</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search ward or room number">
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="ward_asc" @selected($sort === 'ward_asc')>Ward A-Z</option>
                <option value="room_asc" @selected($sort === 'room_asc')>Room asc</option>
                <option value="room_desc" @selected($sort === 'room_desc')>Room desc</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.rooms.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Room</h3>
        <form method="POST" action="{{ route('admin.rooms.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
            @csrf
            <select class="rounded border border-slate-300 px-3 py-2" name="ward_id" required>
                <option value="">Select Ward</option>
                @foreach($wards as $ward)
                    <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                @endforeach
            </select>
            <input class="rounded border border-slate-300 px-3 py-2" name="room_number" placeholder="Room Number" required>
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
                        <a class="text-xs text-sky-700" href="{{ route('admin.rooms.index', array_merge(request()->query(), ['sort' => 'ward_asc'])) }}">asc</a>
                    </th>
                    <th class="py-2 pr-2">
                        Room Number
                        <a class="text-xs text-sky-700" href="{{ route('admin.rooms.index', array_merge(request()->query(), ['sort' => 'room_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.rooms.index', array_merge(request()->query(), ['sort' => 'room_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rooms as $room)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-2">{{ $room->ward_name }}</td>
                        <td class="py-3 pr-2">{{ $room->room_number }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.rooms.update', $room->id) }}" class="mt-2 grid gap-2 min-w-[230px]">
                                    @csrf
                                    @method('PUT')
                                    <select class="rounded border border-slate-300 px-2 py-1" name="ward_id" required>
                                        @foreach($wards as $ward)
                                            <option value="{{ $ward->id }}" @selected($room->ward_id === $ward->id)>{{ $ward->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="room_number" value="{{ $room->room_number }}" required>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.rooms.destroy', $room->id) }}" onsubmit="return confirm('Delete this room?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded bg-rose-600 hover:bg-rose-700 text-white px-3 py-1" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">{{ $rooms->links() }}</div>
    </div>
@endsection
