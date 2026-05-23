<?php

namespace Tests\Feature\Web;

use App\Models\Device;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_sort_patients_from_query(): void
    {
        [$organization, $admin] = $this->createAdmin();

        Patient::query()->create([
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-2002',
            'full_name' => 'Zed Patient',
            'is_active' => true,
        ]);

        Patient::query()->create([
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-2001',
            'full_name' => 'Ada Patient',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/patients?sort=name_asc');

        $response->assertOk();
        $response->assertSeeInOrder(['Ada Patient', 'Zed Patient']);
    }

    public function test_patients_pagination_links_preserve_filter_and_sort_query(): void
    {
        [$organization, $admin] = $this->createAdmin();

        for ($i = 1; $i <= 16; $i++) {
            Patient::query()->create([
                'organization_id' => $organization->id,
                'medical_record_no' => 'MR-A'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'full_name' => 'Alice '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);
        }

        Patient::query()->create([
            'organization_id' => $organization->id,
            'medical_record_no' => 'MR-B01',
            'full_name' => 'Bob Inactive',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get('/admin/patients?q=Alice&active=1&sort=name_asc');

        $response->assertOk();
        $response->assertDontSee('Bob Inactive');
        $response->assertSee('Alice 01');

        // links() should keep query string values on pagination URLs.
        $response->assertSee('q=Alice', false);
        $response->assertSee('active=1', false);
        $response->assertSee('sort=name_asc', false);
        $response->assertSee('page=2', false);
    }

    public function test_super_admin_can_sort_devices_from_query(): void
    {
        [$organization, $superAdmin] = $this->createSuperAdmin();

        Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'DEV-Z-02',
            'mqtt_topic' => 'org/test/DEV-Z-02',
            'status' => 'online',
        ]);

        Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'DEV-A-01',
            'mqtt_topic' => 'org/test/DEV-A-01',
            'status' => 'online',
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/devices?sort=serial_asc');

        $response->assertOk();
        $response->assertSeeInOrder(['DEV-A-01', 'DEV-Z-02']);
    }

    public function test_super_admin_can_create_device_for_selected_organization(): void
    {
        [, $superAdmin] = $this->createSuperAdmin();

        $targetOrganization = Organization::query()->create([
            'name' => 'Target Organization',
            'code' => 'TARGET-ORG',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post('/admin/devices', [
            'organization_id' => $targetOrganization->id,
            'serial_number' => 'DEV-TARGET-01',
            'mqtt_topic' => 'device_target_01',
            'status' => 'online',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'organization_id' => $targetOrganization->id,
            'serial_number' => 'DEV-TARGET-01',
            'mqtt_topic' => 'device_target_01',
        ]);
    }

    public function test_admin_cannot_access_device_management(): void
    {
        [$organization, $admin] = $this->createAdmin();

        Device::query()->create([
            'organization_id' => $organization->id,
            'serial_number' => 'DEV-A-01',
            'mqtt_topic' => 'device_1-c51c',
            'status' => 'online',
        ]);

        $response = $this->actingAs($admin)->get('/admin/devices');

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_organization_and_user_management_pages(): void
    {
        [$organization, $superAdmin] = $this->createSuperAdmin();

        Organization::query()->create([
            'name' => 'Another Org',
            'code' => 'ANOTHER',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Managed User',
            'email' => 'managed@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $organizationsPage = $this->actingAs($superAdmin)->get('/admin/organizations');
        $organizationsPage->assertOk();
        $organizationsPage->assertSee('Organizations');

        $usersPage = $this->actingAs($superAdmin)->get('/admin/users');
        $usersPage->assertOk();
        $usersPage->assertSee('Users');
        $usersPage->assertSee('managed@example.com');
    }

    public function test_admin_cannot_access_organization_and_user_management_pages(): void
    {
        [, $admin] = $this->createAdmin();

        $this->actingAs($admin)->get('/admin/organizations')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
    }

    public function test_dashboard_shows_superadmin_totals_only_for_superadmin(): void
    {
        [$organization, $superAdmin] = $this->createSuperAdmin();

        Organization::query()->create([
            'name' => 'Extra Org',
            'code' => 'EXTRA-ORG',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Extra User',
            'email' => 'extra-user@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $superAdminDashboard = $this->actingAs($superAdmin)->get('/admin/dashboard');
        $superAdminDashboard->assertOk();
        $superAdminDashboard->assertSee('Total Organizations');
        $superAdminDashboard->assertSee('Total Users');

        [, $admin] = $this->createAdmin();

        $adminDashboard = $this->actingAs($admin)->get('/admin/dashboard');
        $adminDashboard->assertOk();
        $adminDashboard->assertDontSee('Total Organizations');
        $adminDashboard->assertDontSee('Total Users');
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function createAdmin(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Web Test Hospital',
            'code' => 'WEB-TST',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        return [$organization, $admin];
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function createSuperAdmin(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Web Test Hospital',
            'code' => 'WEB-SUP',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        return [$organization, $superAdmin];
    }
}
