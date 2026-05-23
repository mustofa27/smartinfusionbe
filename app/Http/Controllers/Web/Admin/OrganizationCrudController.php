<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationCrudController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $active = (string) $request->query('active', '');
        $sort = (string) $request->query('sort', 'newest');

        $organizationsQuery = Organization::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('timezone', 'like', "%{$q}%");
                });
            })
            ->when(in_array($active, ['1', '0'], true), function ($query) use ($active): void {
                $query->where('is_active', $active === '1');
            });

        match ($sort) {
            'name_asc' => $organizationsQuery->orderBy('name'),
            'name_desc' => $organizationsQuery->orderByDesc('name'),
            'code_asc' => $organizationsQuery->orderBy('code'),
            'code_desc' => $organizationsQuery->orderByDesc('code'),
            default => $organizationsQuery->orderByDesc('id'),
        };

        $organizations = $organizationsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.organizations.index', compact('organizations', 'q', 'active', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'timezone' => ['required', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Organization::query()->create([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Organization created.');
    }

    public function update(Request $request, int $organization): RedirectResponse
    {
        $model = Organization::query()->findOrFail($organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', Rule::unique('organizations', 'code')->ignore($model->id)],
            'timezone' => ['required', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model->fill([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ])->save();

        return back()->with('success', 'Organization updated.');
    }

    public function destroy(int $organization): RedirectResponse
    {
        $model = Organization::query()->findOrFail($organization);
        $model->delete();

        return back()->with('success', 'Organization deleted.');
    }
}