@extends('admin.layout')

@section('content')
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold">Patients</h2>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input class="rounded border border-slate-300 px-3 py-2" name="q" value="{{ $q }}" placeholder="Search name or MRN">
            <select class="rounded border border-slate-300 px-3 py-2" name="active">
                <option value="">All statuses</option>
                <option value="1" @selected($active === '1')>Active</option>
                <option value="0" @selected($active === '0')>Inactive</option>
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="newest" @selected($sort === 'newest')>Newest first</option>
                <option value="name_asc" @selected($sort === 'name_asc')>Name A-Z</option>
                <option value="name_desc" @selected($sort === 'name_desc')>Name Z-A</option>
                <option value="mrn_asc" @selected($sort === 'mrn_asc')>MRN asc</option>
                <option value="mrn_desc" @selected($sort === 'mrn_desc')>MRN desc</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.patients.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Patient</h3>
        <form method="POST" action="{{ route('admin.patients.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
            @csrf
            <input class="rounded border border-slate-300 px-3 py-2" name="medical_record_no" placeholder="Medical Record No" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="full_name" placeholder="Full Name" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="gender" placeholder="Gender">
            <input class="rounded border border-slate-300 px-3 py-2" type="date" name="date_of_birth">
            <input class="rounded border border-slate-300 px-3 py-2" name="notes" placeholder="Notes">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <div class="md:col-span-3">
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2" type="submit">Create</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">
                        MRN
                        <a class="text-xs text-sky-700" href="{{ route('admin.patients.index', array_merge(request()->query(), ['sort' => 'mrn_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.patients.index', array_merge(request()->query(), ['sort' => 'mrn_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">
                        Name
                        <a class="text-xs text-sky-700" href="{{ route('admin.patients.index', array_merge(request()->query(), ['sort' => 'name_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.patients.index', array_merge(request()->query(), ['sort' => 'name_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Gender</th>
                    <th class="py-2 pr-2">DOB</th>
                    <th class="py-2 pr-2">Active</th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($patients as $patient)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="py-3 pr-2">{{ $patient->medical_record_no }}</td>
                        <td class="py-3 pr-2">{{ $patient->full_name }}</td>
                        <td class="py-3 pr-2">{{ $patient->gender }}</td>
                        <td class="py-3 pr-2">{{ optional($patient->date_of_birth)->format('Y-m-d') }}</td>
                        <td class="py-3 pr-2">{{ $patient->is_active ? 'Yes' : 'No' }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.patients.update', $patient->id) }}" class="mt-2 grid gap-2 min-w-[260px]">
                                    @csrf
                                    @method('PUT')
                                    <input class="rounded border border-slate-300 px-2 py-1" name="medical_record_no" value="{{ $patient->medical_record_no }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="full_name" value="{{ $patient->full_name }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="gender" value="{{ $patient->gender }}">
                                    <input class="rounded border border-slate-300 px-2 py-1" type="date" name="date_of_birth" value="{{ optional($patient->date_of_birth)->format('Y-m-d') }}">
                                    <input class="rounded border border-slate-300 px-2 py-1" name="notes" value="{{ $patient->notes }}">
                                    <label class="text-xs flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($patient->is_active)>
                                        Active
                                    </label>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.patients.destroy', $patient->id) }}" onsubmit="return confirm('Delete this patient?')">
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
            {{ $patients->links() }}
        </div>
    </div>
@endsection
