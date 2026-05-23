<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_nurse_can_login_with_organization_code(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-01',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Nurse One',
            'email' => 'nurse@example.com',
            'password' => 'secret123',
            'role' => 'nurse',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'organization_code' => 'GH-01',
            'email' => 'nurse@example.com',
            'password' => 'secret123',
            'device_name' => 'Android',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'organization_id'],
            ]);
    }
}
