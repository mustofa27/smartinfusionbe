<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomCrudController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');

        $wards = Ward::query()
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $roomsQuery = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $orgId)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('rooms.room_number', 'like', "%{$q}%")
                        ->orWhere('wards.name', 'like', "%{$q}%");
                });
            })
            ->select(['rooms.*', 'wards.name as ward_name']);

        match ($sort) {
            'ward_asc' => $roomsQuery->orderBy('wards.name')->orderBy('rooms.room_number'),
            'room_asc' => $roomsQuery->orderBy('rooms.room_number'),
            'room_desc' => $roomsQuery->orderByDesc('rooms.room_number'),
            default => $roomsQuery->orderByDesc('rooms.id'),
        };

        $rooms = $roomsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.rooms.index', compact('rooms', 'wards', 'q', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'ward_id' => ['required', 'integer', 'min:1'],
            'room_number' => ['required', 'string', 'max:40'],
        ]);

        $ward = Ward::query()->where('organization_id', $orgId)->findOrFail($validated['ward_id']);

        $request->validate([
            'room_number' => [
                Rule::unique('rooms')->where(fn ($q) => $q->where('ward_id', $ward->id)),
            ],
        ]);

        Room::query()->create([
            'ward_id' => $ward->id,
            'room_number' => $validated['room_number'],
        ]);

        return back()->with('success', 'Room created.');
    }

    public function update(Request $request, int $room): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Room::query()->findOrFail($room);

        $validated = $request->validate([
            'ward_id' => ['required', 'integer', 'min:1'],
            'room_number' => ['required', 'string', 'max:40'],
        ]);

        $ward = Ward::query()->where('organization_id', $orgId)->findOrFail($validated['ward_id']);

        $request->validate([
            'room_number' => [
                Rule::unique('rooms')->where(fn ($q) => $q->where('ward_id', $ward->id))->ignore($model->id),
            ],
        ]);

        $model->fill([
            'ward_id' => $ward->id,
            'room_number' => $validated['room_number'],
        ])->save();

        return back()->with('success', 'Room updated.');
    }

    public function destroy(Request $request, int $room): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Room::query()->findOrFail($room);
        Ward::query()->where('organization_id', $orgId)->findOrFail($model->ward_id);
        $model->delete();

        return back()->with('success', 'Room deleted.');
    }
}
