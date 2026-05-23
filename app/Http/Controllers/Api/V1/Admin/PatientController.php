<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $patients = Patient::query()
            ->where('organization_id', $user->organization_id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($patients);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'medical_record_no' => [
                'required',
                'string',
                'max:80',
                Rule::unique('patients')->where(fn ($q) => $q->where('organization_id', $user->organization_id)),
            ],
            'full_name' => ['required', 'string', 'max:160'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $patient = Patient::query()->create([
            ...$validated,
            'organization_id' => $user->organization_id,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $patient], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $patient = Patient::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        return response()->json(['data' => $patient]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $patient = Patient::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'medical_record_no' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('patients')
                    ->where(fn ($q) => $q->where('organization_id', $user->organization_id))
                    ->ignore($patient->id),
            ],
            'full_name' => ['sometimes', 'required', 'string', 'max:160'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $patient->fill($validated);
        $patient->save();

        return response()->json(['data' => $patient]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $patient = Patient::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        $patient->delete();

        return response()->json(['message' => 'Patient deleted.']);
    }
}
