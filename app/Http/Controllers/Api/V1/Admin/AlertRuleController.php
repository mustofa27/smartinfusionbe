<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertRule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(
            AlertRule::query()
                ->where('organization_id', $user->organization_id)
                ->orderBy('code')
                ->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'code' => [
                'required',
                Rule::in(['low_volume', 'no_flow', 'device_offline']),
                Rule::unique('alert_rules')->where(fn ($q) => $q->where('organization_id', $user->organization_id)),
            ],
            'threshold_value' => ['required', 'numeric'],
            'threshold_unit' => ['required', 'string', 'max:30'],
            'cooldown_seconds' => ['nullable', 'integer', 'min:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = AlertRule::query()->create([
            ...$validated,
            'organization_id' => $user->organization_id,
            'cooldown_seconds' => $validated['cooldown_seconds'] ?? 300,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $rule], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rule = AlertRule::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        return response()->json(['data' => $rule]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rule = AlertRule::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'threshold_value' => ['sometimes', 'required', 'numeric'],
            'threshold_unit' => ['sometimes', 'required', 'string', 'max:30'],
            'cooldown_seconds' => ['sometimes', 'required', 'integer', 'min:30'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule->fill($validated)->save();

        return response()->json(['data' => $rule]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rule = AlertRule::query()
            ->where('organization_id', $user->organization_id)
            ->findOrFail($id);

        $rule->delete();

        return response()->json(['message' => 'Alert rule deleted.']);
    }
}
