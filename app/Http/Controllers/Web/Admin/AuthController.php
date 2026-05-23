<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $organization = Organization::query()
            ->where('code', $validated['organization_code'])
            ->where('is_active', true)
            ->first();

        if (! $organization) {
            return back()->withErrors([
                'organization_code' => 'Organization not found or inactive.',
            ])->onlyInput('organization_code', 'email');
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('organization_id', $organization->id)
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ])->onlyInput('organization_code', 'email');
        }

        if (! $user->is_active || ! in_array($user->role, ['admin', 'super-admin'], true)) {
            return back()->withErrors([
                'email' => 'Admin or super-admin account is required.',
            ])->onlyInput('organization_code', 'email');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
