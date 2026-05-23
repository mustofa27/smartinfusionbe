<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientCrudController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $q = trim((string) $request->query('q', ''));
        $active = (string) $request->query('active', '');
        $sort = (string) $request->query('sort', 'newest');

        $patientsQuery = Patient::query()
            ->where('organization_id', $orgId)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('full_name', 'like', "%{$q}%")
                        ->orWhere('medical_record_no', 'like', "%{$q}%");
                });
            })
            ->when(in_array($active, ['1', '0'], true), function ($query) use ($active): void {
                $query->where('is_active', $active === '1');
            });

        match ($sort) {
            'name_asc' => $patientsQuery->orderBy('full_name'),
            'name_desc' => $patientsQuery->orderByDesc('full_name'),
            'mrn_asc' => $patientsQuery->orderBy('medical_record_no'),
            'mrn_desc' => $patientsQuery->orderByDesc('medical_record_no'),
            default => $patientsQuery->orderByDesc('id'),
        };

        $patients = $patientsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.patients.index', compact('patients', 'q', 'active', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'medical_record_no' => [
                'required',
                'string',
                'max:80',
                Rule::unique('patients')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'full_name' => ['required', 'string', 'max:160'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Patient::query()->create([
            ...$validated,
            'organization_id' => $orgId,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Patient created.');
    }

    public function update(Request $request, int $patient): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Patient::query()->where('organization_id', $orgId)->findOrFail($patient);

        $validated = $request->validate([
            'medical_record_no' => [
                'required',
                'string',
                'max:80',
                Rule::unique('patients')->where(fn ($q) => $q->where('organization_id', $orgId))->ignore($model->id),
            ],
            'full_name' => ['required', 'string', 'max:160'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model->fill([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        return back()->with('success', 'Patient updated.');
    }

    public function destroy(Request $request, int $patient): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Patient::query()->where('organization_id', $orgId)->findOrFail($patient);
        $model->delete();

        return back()->with('success', 'Patient deleted.');
    }
}
