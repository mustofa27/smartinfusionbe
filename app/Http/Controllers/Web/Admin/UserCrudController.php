<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserCrudController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $sort = (string) $request->query('sort', 'newest');

        $usersQuery = User::query()
            ->leftJoin('organizations', 'organizations.id', '=', 'users.organization_id')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($nested) use ($q): void {
                    $nested->where('users.name', 'like', "%{$q}%")
                        ->orWhere('users.email', 'like', "%{$q}%")
                        ->orWhere('organizations.name', 'like', "%{$q}%")
                        ->orWhere('organizations.code', 'like', "%{$q}%");
                });
            })
            ->when(in_array($role, ['super-admin', 'admin', 'nurse'], true), function ($query) use ($role): void {
                $query->where('users.role', $role);
            })
            ->select(['users.*', 'organizations.name as organization_name', 'organizations.code as organization_code']);

        match ($sort) {
            'name_asc' => $usersQuery->orderBy('users.name'),
            'name_desc' => $usersQuery->orderByDesc('users.name'),
            'email_asc' => $usersQuery->orderBy('users.email'),
            'email_desc' => $usersQuery->orderByDesc('users.email'),
            default => $usersQuery->orderByDesc('users.id'),
        };

        $users = $usersQuery
            ->paginate(15)
            ->withQueryString();

        $organizations = Organization::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.users.index', compact('users', 'organizations', 'q', 'role', 'sort'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super-admin', 'admin', 'nurse'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = User::query()
            ->where('organization_id', $validated['organization_id'])
            ->where('email', $validated['email'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['email' => 'Email already exists in this organization.'])
                ->withInput();
        }

        User::query()->create([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $model = User::query()->findOrFail($user);

        $validated = $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super-admin', 'admin', 'nurse'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $exists = User::query()
            ->where('organization_id', $validated['organization_id'])
            ->where('email', $validated['email'])
            ->where('id', '!=', $model->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['email' => 'Email already exists in this organization.'])
                ->withInput();
        }

        $updateData = [
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $model->fill($updateData)->save();

        return back()->with('success', 'User updated.');
    }

    public function destroy(int $user): RedirectResponse
    {
        $model = User::query()->findOrFail($user);
        $model->delete();

        return back()->with('success', 'User deleted.');
    }
}