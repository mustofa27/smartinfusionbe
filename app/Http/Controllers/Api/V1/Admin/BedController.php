<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $beds = Bed::query()
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->orderByDesc('beds.id')
            ->select('beds.*')
            ->paginate(20);

        return response()->json($beds);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'min:1'],
            'bed_number' => ['required', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $room = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->where('rooms.id', $validated['room_id'])
            ->select('rooms.*')
            ->firstOrFail();

        $request->validate([
            'bed_number' => [
                Rule::unique('beds')->where(fn ($q) => $q->where('room_id', $room->id)),
            ],
        ]);

        $bed = Bed::query()->create([
            'room_id' => $room->id,
            'bed_number' => $validated['bed_number'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json(['data' => $bed], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bed = Bed::query()
            ->join('rooms', 'rooms.id', '=', 'beds.room_id')
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->where('beds.id', $id)
            ->select('beds.*')
            ->firstOrFail();

        return response()->json(['data' => $bed]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bed = Bed::query()->findOrFail($id);

        $room = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->where('rooms.id', $bed->room_id)
            ->select('rooms.*')
            ->firstOrFail();

        $validated = $request->validate([
            'bed_number' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('beds')->where(fn ($q) => $q->where('room_id', $room->id))->ignore($bed->id),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $bed->fill($validated)->save();

        return response()->json(['data' => $bed]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bed = Bed::query()->findOrFail($id);

        Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->where('rooms.id', $bed->room_id)
            ->select('rooms.id')
            ->firstOrFail();

        $bed->delete();

        return response()->json(['message' => 'Bed deleted.']);
    }
}
