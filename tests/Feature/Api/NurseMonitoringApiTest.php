<?php

namespace Tests\Feature\Api;

use App\Models\Bed;
use App\Models\Device;
use App\Models\InfusionSession;
use App\Models\NurseDeviceSubscription;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\Room;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NurseMonitoringApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_by_device_code_returns_session_required_when_no_active_session(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-01',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $nurse = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'nurse',
            'is_active' => true,
        ]);

        Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'INFUS-001',
            'mqtt_topic' => 'org/GH-01/device/INFUS-001/reading',
            'status' => 'online',
        ]);

        Sanctum::actingAs($nurse, ['nurse']);

        $response = $this->postJson('/api/v1/nurse/monitor/by-device-code', [
            'device_code' => 'INFUS-001',
        ]);

        $response->assertOk()
            ->assertJson([
                'session_required' => true,
                'device' => [
                    'serial_number' => 'INFUS-001',
                ],
            ])
            ->assertJsonStructure([
                'required_fields',
            ]);
    }

    public function test_nurse_can_pause_complete_and_interrupt_session_in_scope(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-02',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $nurse = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'nurse',
            'is_active' => true,
        ]);

        $ward = Ward::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Ward A',
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

        $patient = Patient::query()->create([
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-2001',
            'full_name' => 'Patient One',
            'is_active' => true,
        ]);

        $device = Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'INFUS-2001',
            'mqtt_topic' => 'org/GH-02/device/INFUS-2001/reading',
            'status' => 'online',
        ]);

        NurseDeviceSubscription::query()->create([
            'organization_id' => $organization->id,
            'nurse_user_id' => $nurse->id,
            'device_id' => $device->id,
        ]);

        $session = InfusionSession::query()->create([
            'organization_id' => $organization->id,
            'patient_id' => $patient->id,
            'device_id' => $device->id,
            'bed_id' => $bed->id,
            'started_by_user_id' => $nurse->id,
            'fluid_name' => 'NaCl',
            'bag_volume_ml' => 500,
            'bag_empty_weight_grams' => 50,
            'initial_weight_grams' => 550,
            'started_at' => now(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($nurse, ['nurse']);

        $pauseResponse = $this->postJson("/api/v1/nurse/infusion-sessions/{$session->id}/pause", [
            'notes' => 'Temporarily paused',
        ]);

        $pauseResponse->assertOk()->assertJsonPath('data.status', 'paused');

        $this->assertDatabaseHas('infusion_sessions', [
            'id' => $session->id,
            'status' => 'paused',
            'notes' => 'Temporarily paused',
        ]);

        $completeResponse = $this->postJson("/api/v1/nurse/infusion-sessions/{$session->id}/complete", [
            'notes' => 'Session completed',
        ]);

        $completeResponse->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('infusion_sessions', [
            'id' => $session->id,
            'status' => 'completed',
            'ended_by_user_id' => $nurse->id,
            'notes' => 'Session completed',
        ]);

        $patientTwo = Patient::query()->create([
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-2002',
            'full_name' => 'Patient Two',
            'is_active' => true,
        ]);

        $sessionTwo = InfusionSession::query()->create([
            'organization_id' => $organization->id,
            'patient_id' => $patientTwo->id,
            'device_id' => $device->id,
            'bed_id' => $bed->id,
            'started_by_user_id' => $nurse->id,
            'fluid_name' => 'D5W',
            'bag_volume_ml' => 500,
            'bag_empty_weight_grams' => 50,
            'initial_weight_grams' => 550,
            'started_at' => now(),
            'status' => 'active',
        ]);

        $interruptResponse = $this->postJson("/api/v1/nurse/infusion-sessions/{$sessionTwo->id}/interrupt", [
            'notes' => 'Line issue',
        ]);

        $interruptResponse->assertOk()->assertJsonPath('data.status', 'interrupted');

        $this->assertDatabaseHas('infusion_sessions', [
            'id' => $sessionTwo->id,
            'status' => 'interrupted',
            'ended_by_user_id' => $nurse->id,
            'notes' => 'Line issue',
        ]);
    }

    public function test_nurse_cannot_update_session_outside_subscription_scope(): void
    {
        $organization = Organization::query()->create([
            'name' => 'General Hospital',
            'code' => 'GH-03',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $nurse = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'nurse',
            'is_active' => true,
        ]);

        $ward = Ward::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Ward B',
        ]);

        $room = Room::query()->create([
            'ward_id' => $ward->id,
            'room_number' => '201',
        ]);

        $bed = Bed::query()->create([
            'room_id' => $room->id,
            'bed_number' => '2',
            'status' => 'active',
        ]);

        $patient = Patient::query()->create([
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-3001',
            'full_name' => 'Patient Three',
            'is_active' => true,
        ]);

        $device = Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'INFUS-3001',
            'mqtt_topic' => 'org/GH-03/device/INFUS-3001/reading',
            'status' => 'online',
        ]);

        $session = InfusionSession::query()->create([
            'organization_id' => $organization->id,
            'patient_id' => $patient->id,
            'device_id' => $device->id,
            'bed_id' => $bed->id,
            'started_by_user_id' => $nurse->id,
            'fluid_name' => 'NaCl',
            'bag_volume_ml' => 500,
            'bag_empty_weight_grams' => 50,
            'initial_weight_grams' => 550,
            'started_at' => now(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($nurse, ['nurse']);

        $response = $this->postJson("/api/v1/nurse/infusion-sessions/{$session->id}/pause");

        $response->assertNotFound();
    }
}
