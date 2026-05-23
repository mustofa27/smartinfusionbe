@extends('admin.layout')

@section('content')
    <h2 class="text-2xl font-semibold">Alert Rules</h2>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <select class="rounded border border-slate-300 px-3 py-2" name="code">
                <option value="">All codes</option>
                @foreach (['low_volume', 'no_flow', 'device_offline'] as $opt)
                    <option value="{{ $opt }}" @selected($code === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="active">
                <option value="">All statuses</option>
                <option value="1" @selected($active === '1')>Active</option>
                <option value="0" @selected($active === '0')>Inactive</option>
            </select>
            <select class="rounded border border-slate-300 px-3 py-2" name="sort">
                <option value="code_asc" @selected($sort === 'code_asc')>Code A-Z</option>
                <option value="code_desc" @selected($sort === 'code_desc')>Code Z-A</option>
                <option value="cooldown_asc" @selected($sort === 'cooldown_asc')>Cooldown low-high</option>
                <option value="cooldown_desc" @selected($sort === 'cooldown_desc')>Cooldown high-low</option>
            </select>
            <div class="flex gap-2">
                <button class="rounded bg-slate-700 hover:bg-slate-800 text-white px-4 py-2" type="submit">Filter</button>
                <a class="rounded bg-slate-200 hover:bg-slate-300 text-slate-900 px-4 py-2" href="{{ route('admin.alert-rules.index') }}">Reset</a>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6">
        <h3 class="font-semibold">Create Alert Rule</h3>
        <form method="POST" action="{{ route('admin.alert-rules.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 mt-3">
            @csrf
            <select class="rounded border border-slate-300 px-3 py-2" name="code" required>
                <option value="low_volume">low_volume</option>
                <option value="no_flow">no_flow</option>
                <option value="device_offline">device_offline</option>
            </select>
            <input class="rounded border border-slate-300 px-3 py-2" name="threshold_value" type="number" step="0.0001" placeholder="Threshold" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="threshold_unit" placeholder="Unit" required>
            <input class="rounded border border-slate-300 px-3 py-2" name="cooldown_seconds" type="number" min="30" value="300" required>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <div class="md:col-span-5">
                <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2" type="submit">Create</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-5 mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b border-slate-200">
                    <th class="py-2 pr-2">
                        Code
                        <a class="text-xs text-sky-700" href="{{ route('admin.alert-rules.index', array_merge(request()->query(), ['sort' => 'code_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.alert-rules.index', array_merge(request()->query(), ['sort' => 'code_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Threshold</th>
                    <th class="py-2 pr-2">Unit</th>
                    <th class="py-2 pr-2">
                        Cooldown
                        <a class="text-xs text-sky-700" href="{{ route('admin.alert-rules.index', array_merge(request()->query(), ['sort' => 'cooldown_asc'])) }}">asc</a>
                        <a class="text-xs text-sky-700" href="{{ route('admin.alert-rules.index', array_merge(request()->query(), ['sort' => 'cooldown_desc'])) }}">desc</a>
                    </th>
                    <th class="py-2 pr-2">Active</th>
                    <th class="py-2 pr-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rules as $rule)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="py-3 pr-2">{{ $rule->code }}</td>
                        <td class="py-3 pr-2">{{ $rule->threshold_value }}</td>
                        <td class="py-3 pr-2">{{ $rule->threshold_unit }}</td>
                        <td class="py-3 pr-2">{{ $rule->cooldown_seconds }}</td>
                        <td class="py-3 pr-2">{{ $rule->is_active ? 'Yes' : 'No' }}</td>
                        <td class="py-3 pr-2">
                            <details>
                                <summary class="cursor-pointer text-sky-700">Edit</summary>
                                <form method="POST" action="{{ route('admin.alert-rules.update', $rule->id) }}" class="mt-2 grid gap-2 min-w-[260px]">
                                    @csrf
                                    @method('PUT')
                                    <select class="rounded border border-slate-300 px-2 py-1" name="code" required>
                                        @foreach (['low_volume', 'no_flow', 'device_offline'] as $opt)
                                            <option value="{{ $opt }}" @selected($rule->code === $opt)>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="threshold_value" type="number" step="0.0001" value="{{ $rule->threshold_value }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="threshold_unit" value="{{ $rule->threshold_unit }}" required>
                                    <input class="rounded border border-slate-300 px-2 py-1" name="cooldown_seconds" type="number" min="30" value="{{ $rule->cooldown_seconds }}" required>
                                    <label class="text-xs flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($rule->is_active)>
                                        Active
                                    </label>
                                    <button class="rounded bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1" type="submit">Save</button>
                                </form>
                            </details>
                            <form class="mt-2" method="POST" action="{{ route('admin.alert-rules.destroy', $rule->id) }}" onsubmit="return confirm('Delete this rule?')">
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
            {{ $rules->links() }}
        </div>
    </div>
@endsection
