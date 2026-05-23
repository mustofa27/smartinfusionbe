<?php

namespace Tests\Feature\Api;

use App\Models\Bed;
use App\Models\Device;
use App\Models\Room;
use App\Models\Organization;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCrudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_patient(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-ADM',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $response = $this->postJson('/api/v1/admin/patients', [
            'medical_record_no' => 'MR-1001',
            'full_name' => 'John Doe',
            'gender' => 'male',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.medical_record_no', 'MR-1001');

        $this->assertDatabaseHas('patients', [
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-1001',
            'full_name' => 'John Doe',
        ]);
    }

    public function test_super_admin_can_access_device_crud(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-SUPER',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($superAdmin, ['admin', 'super-admin']);

        $response = $this->getJson('/api/v1/admin/devices');

        $response->assertOk();
    }

    public function test_admin_cannot_access_device_crud(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-ADM-LOCK',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/v1/admin/devices');

        $response->assertForbidden();
    }

    public function test_admin_can_create_device_room_assignment(): void
    {
        [$organization, $ward, $room, $bed, $device] = $this->seedDeviceAssignmentScope();

        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $response = $this->postJson('/api/v1/device-assignments', [
            'device_id' => $device->id,
            'bed_id' => $bed->id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('device_bed_assignments', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'bed_id' => $bed->id,
            'mounted_by_user_id' => $admin->id,
        ]);
    }

    public function test_nurse_can_create_device_room_assignment(): void
    {
        [$organization, $ward, $room, $bed, $device] = $this->seedDeviceAssignmentScope();

        $nurse = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'nurse',
            'is_active' => true,
        ]);

        Sanctum::actingAs($nurse, ['nurse']);

        $response = $this->postJson('/api/v1/device-assignments', [
            'device_id' => $device->id,
            'bed_id' => $bed->id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('device_bed_assignments', [
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'bed_id' => $bed->id,
            'mounted_by_user_id' => $nurse->id,
        ]);
    }

    public function test_nurse_cannot_access_admin_crud(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-NURSE',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $nurse = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'nurse',
            'is_active' => true,
        ]);

        Sanctum::actingAs($nurse, ['nurse']);

        $response = $this->getJson('/api/v1/admin/patients');

        $response->assertForbidden();
    }

    /**
     * @return array{0: Organization, 1: Ward, 2: Room, 3: Bed, 4: Device}
     */
    private function seedDeviceAssignmentScope(): array
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-ASSIGN-'.str()->random(6),
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $ward = Ward::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Ward A',
            'floor' => '1',
        ]);

        $room = Room::query()->create([
            'ward_id' => $ward->id,
            'room_number' => '101',
        ]);

        $bed = Bed::query()->create([
            'room_id' => $room->id,
            'bed_number' => '1',
            'status' => 'active',
        ]);

        $device = Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'DEV-'.str()->random(8),
            'mqtt_topic' => 'device_'.str()->random(8),
            'status' => 'online',
        ]);

        return [$organization, $ward, $room, $bed, $device];
    }
}
