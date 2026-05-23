<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BedCrudController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'newest');

        $rooms = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->orderBy('rooms.room_number')
            ->select(['rooms.id', 'rooms.room_number', 'wards.name as ward_name'])
            ->get();

        $bedsQuery = Bed::query()
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('beds.bed_number', 'like', "%{$q}%")
                        ->orWhere('rooms.room_number', 'like', "%{$q}%")
                        ->orWhere('wards.name', 'like', "%{$q}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive', 'maintenance'], true), function ($query) use ($status): void {
                $query->where('beds.status', $status);
            })
            ->select([
                'beds.*',
                'rooms.room_number',
                'wards.name as ward_name',
            ]);

        match ($sort) {
            'ward_asc' => $bedsQuery->orderBy('wards.name')->orderBy('rooms.room_number')->orderBy('beds.bed_number'),
            'bed_asc' => $bedsQuery->orderBy('beds.bed_number'),
            'bed_desc' => $bedsQuery->orderByDesc('beds.bed_number'),
            'status_asc' => $bedsQuery->orderBy('beds.status'),
            default => $bedsQuery->orderByDesc('beds.id'),
        };

        $beds = $bedsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.beds.index', compact('beds', 'rooms', 'q', 'status', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'min:1'],
            'bed_number' => ['required', 'string', 'max:40'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $room = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->where('rooms.id', $validated['room_id'])
            ->select('rooms.*')
            ->firstOrFail();

        $request->validate([
            'bed_number' => [
                Rule::unique('beds')->where(fn ($q) => $q->where('room_id', $room->id)),
            ],
        ]);

        Bed::query()->create([
            'room_id' => $room->id,
            'bed_number' => $validated['bed_number'],
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Bed created.');
    }

    public function update(Request $request, int $bed): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Bed::query()->findOrFail($bed);

        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'min:1'],
            'bed_number' => ['required', 'string', 'max:40'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $room = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->where('rooms.id', $validated['room_id'])
            ->select('rooms.*')
            ->firstOrFail();

        $request->validate([
            'bed_number' => [
                Rule::unique('beds')->where(fn ($q) => $q->where('room_id', $room->id))->ignore($model->id),
            ],
        ]);

        $model->fill([
            'room_id' => $room->id,
            'bed_number' => $validated['bed_number'],
            'status' => $validated['status'],
        ])->save();

        return back()->with('success', 'Bed updated.');
    }

    public function destroy(Request $request, int $bed): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Bed::query()->findOrFail($bed);

        Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->where('rooms.id', $model->room_id)
            ->select('rooms.id')
            ->firstOrFail();

        $model->delete();

        return back()->with('success', 'Bed deleted.');
    }
}
