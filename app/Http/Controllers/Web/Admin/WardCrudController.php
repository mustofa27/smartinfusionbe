<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WardCrudController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = (int) $request->user()->organization_id;
        $q = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'name_asc');

        $wardsQuery = Ward::query()
            ->where('organization_id', $orgId)
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('name', 'like', "%{$q}%")
                        ->orWhere('floor', 'like', "%{$q}%");
                });
            });

        match ($sort) {
            'name_desc' => $wardsQuery->orderByDesc('name'),
            'floor_asc' => $wardsQuery->orderBy('floor'),
            'floor_desc' => $wardsQuery->orderByDesc('floor'),
            'newest' => $wardsQuery->orderByDesc('id'),
            default => $wardsQuery->orderBy('name'),
        };

        $wards = $wardsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.wards.index', compact('wards', 'q', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('wards')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'floor' => ['nullable', 'string', 'max:30'],
        ]);

        Ward::query()->create([
            ...$validated,
            'organization_id' => $orgId,
        ]);

        return back()->with('success', 'Ward created.');
    }

    public function update(Request $request, int $ward): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Ward::query()->where('organization_id', $orgId)->findOrFail($ward);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('wards')->where(fn ($q) => $q->where('organization_id', $orgId))->ignore($model->id),
            ],
            'floor' => ['nullable', 'string', 'max:30'],
        ]);

        $model->fill($validated)->save();

        return back()->with('success', 'Ward updated.');
    }

    public function destroy(Request $request, int $ward): RedirectResponse
    {
        $orgId = (int) $request->user()->organization_id;

        $model = Ward::query()->where('organization_id', $orgId)->findOrFail($ward);
        $model->delete();

        return back()->with('success', 'Ward deleted.');
    }
}
