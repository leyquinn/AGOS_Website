<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_portal_credentials_are_rejected(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_portal_requires_staff_credentials(): void
    {
        $citizen = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'citizen'])->id,
            'email' => 'citizen@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $citizen->email,
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_seeded_super_admin_can_access_barangay_management(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'superadmin@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('admin.barangays'))->assertOk();
    }

    public function test_non_super_admin_cannot_access_barangay_management(): void
    {
        $role = Role::firstOrCreate(['name' => 'barangay_admin']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'barangay@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->actingAs($admin)->get(route('admin.barangays'))->assertForbidden();
    }

    public function test_assigned_barangay_admin_can_log_in_with_the_assigned_credentials(): void
    {
        $superAdmin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'status' => 'approved',
        ]);
        $barangay = \App\Models\Barangay::create([
            'name' => 'Poblacion',
            'district' => '1st District',
        ]);

        $this->actingAs($superAdmin)->post(route('admin.assign-barangay-admin'), [
            'first_name' => 'Barangay',
            'last_name' => 'Admin',
            'email' => 'barangay-admin@example.com',
            'phone_number' => '09171234567',
            'password' => 'Password123!',
            'barangay_id' => $barangay->id,
        ])->assertRedirect(route('admin.barangays'));

        $this->post(route('login.store'), [
            'email' => 'barangay-admin@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(User::where('email', 'barangay-admin@example.com')->first());
    }

    public function test_citizen_cannot_open_admin_portal(): void
    {
        $citizen = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'citizen'])->id,
        ]);

        $this->actingAs($citizen)->get(route('dashboard'))->assertForbidden();
    }
}
