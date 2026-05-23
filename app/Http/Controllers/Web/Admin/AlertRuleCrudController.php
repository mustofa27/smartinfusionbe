<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertRuleCrudController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $code = (string) $request->query('code', '');
        $active = (string) $request->query('active', '');
        $sort = (string) $request->query('sort', 'code_asc');

        $rulesQuery = AlertRule::query()
            ->where('organization_id', $orgId)
            ->when(in_array($code, ['low_volume', 'no_flow', 'device_offline'], true), function ($query) use ($code): void {
                $query->where('code', $code);
            })
            ->when(in_array($active, ['1', '0'], true), function ($query) use ($active): void {
                $query->where('is_active', $active === '1');
            });

        match ($sort) {
            'cooldown_asc' => $rulesQuery->orderBy('cooldown_seconds'),
            'cooldown_desc' => $rulesQuery->orderByDesc('cooldown_seconds'),
            'code_desc' => $rulesQuery->orderByDesc('code'),
            default => $rulesQuery->orderBy('code'),
        };

        $rules = $rulesQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.alert-rules.index', compact('rules', 'code', 'active', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'code' => [
                'required',
                Rule::in(['low_volume', 'no_flow', 'device_offline']),
                Rule::unique('alert_rules')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'threshold_value' => ['required', 'numeric'],
            'threshold_unit' => ['required', 'string', 'max:30'],
            'cooldown_seconds' => ['required', 'integer', 'min:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        AlertRule::query()->create([
            ...$validated,
            'organization_id' => $orgId,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Alert rule created.');
    }

    public function update(Request $request, int $alertRule): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = AlertRule::query()->where('organization_id', $orgId)->findOrFail($alertRule);

        $validated = $request->validate([
            'threshold_value' => ['required', 'numeric'],
            'threshold_unit' => ['required', 'string', 'max:30'],
            'cooldown_seconds' => ['required', 'integer', 'min:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model->fill([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        return back()->with('success', 'Alert rule updated.');
    }

    public function destroy(Request $request, int $alertRule): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;
        $model = AlertRule::query()->where('organization_id', $orgId)->findOrFail($alertRule);
        $model->delete();

        return back()->with('success', 'Alert rule deleted.');
    }
}
