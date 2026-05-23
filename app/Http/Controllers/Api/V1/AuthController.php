<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $organization = Organization::query()
            ->where('code', $validated['organization_code'])
            ->where('is_active', true)
            ->first();

        if (! $organization) {
            throw ValidationException::withMessages([
                'organization_code' => ['Organization not found or inactive.'],
            ]);
        }

        /** @var User|null $user */
        $user = User::query()
            ->where('organization_id', $organization->id)
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive.'],
            ]);
        }

        $abilities = match ($user->role) {
            'super-admin' => ['admin', 'super-admin'],
            'admin' => ['admin'],
            default => ['nurse'],
        };
        $tokenName = $validated['device_name'] ?? 'mobile-app';
        $plainTextToken = $user->createToken($tokenName, $abilities)->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'organization_id' => $user->organization_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
