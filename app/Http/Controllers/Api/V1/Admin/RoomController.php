<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rooms = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->orderByDesc('rooms.id')
            ->select('rooms.*')
            ->paginate(20);

        return response()->json($rooms);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'ward_id' => ['required', 'integer', 'min:1'],
            'room_number' => ['required', 'string', 'max:40'],
        ]);

        $ward = Ward::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($validated['ward_id']);

        $request->validate([
            'room_number' => [
                Rule::unique('rooms')->where(fn ($q) => $q->where('ward_id', $ward->id)),
            ],
        ]);

        $room = Room::query()->create([
            'ward_id' => $ward->id,
            'room_number' => $validated['room_number'],
        ]);

        return response()->json(['data' => $room], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $room = Room::query()
            ->join('wards', 'wards.id', '=', 'rooms.ward_id')
            ->where('wards.organization_id', $user->organization_id)
            ->where('rooms.id', $id)
            ->select('rooms.*')
            ->firstOrFail();

        return response()->json(['data' => $room]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $room = Room::query()->findOrFail($id);
        $ward = Ward::query()->where('organization_id', $user->organization_id)->findOrFail($room->ward_id);

        $validated = $request->validate([
            'room_number' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                Rule::unique('rooms')->where(fn ($q) => $q->where('ward_id', $ward->id))->ignore($room->id),
            ],
        ]);

        $room->fill($validated)->save();

        return response()->json(['data' => $room]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $room = Room::query()->findOrFail($id);
        Ward::query()->where('organization_id', $user->organization_id)->findOrFail($room->ward_id);

        $room->delete();

        return response()->json(['message' => 'Room deleted.']);
    }
}
