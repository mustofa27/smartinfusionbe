<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(
            Ward::query()->where('organization_id', $user->organization_id)->orderBy('name')->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('wards')->where(fn ($q) => $q->where('organization_id', $user->organization_id)),
            ],
            'floor' => ['nullable', 'string', 'max:30'],
        ]);

        $ward = Ward::query()->create([
            ...$validated,
            'organization_id' => $user->organization_id,
        ]);

        return response()->json(['data' => $ward], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ward = Ward::query()->where('organization_id', $user->organization_id)->findOrFail($id);

        return response()->json(['data' => $ward]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ward = Ward::query()->where('organization_id', $user->organization_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('wards')
                    ->where(fn ($q) => $q->where('organization_id', $user->organization_id))
                    ->ignore($ward->id),
            ],
            'floor' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);

        $ward->fill($validated)->save();

        return response()->json(['data' => $ward]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $ward = Ward::query()->where('organization_id', $user->organization_id)->findOrFail($id);

        $ward->delete();

        return response()->json(['message' => 'Ward deleted.']);
    }
}
