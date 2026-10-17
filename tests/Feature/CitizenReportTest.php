<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_app_is_available_from_the_login_flow(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('citizen.register'));
    }

    public function test_authenticated_users_cannot_open_the_citizen_registration_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('citizen.register'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_citizen_can_submit_a_flood_report(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('citizen.reports.store'), [
                'reporter_name' => 'Maria Santos',
                'reporter_contact' => '09171234567',
                'location' => 'Barangay Matina',
                'water_level' => 'knee',
                'description' => 'Water is rising near the elementary school.',
            ]);

        $response->assertRedirect(route('citizen.create'));
        $this->assertDatabaseHas('flood_reports', [
            'location' => 'Barangay Matina',
            'water_level' => 'knee',
            'status' => 'pending',
        ]);
    }

    public function test_citizen_app_requires_authentication(): void
    {
        $this->get(route('citizen.create'))
            ->assertRedirect(route('citizen.login'));
    }

    public function test_citizen_can_register_and_is_signed_in(): void
    {
        $response = $this->post(route('citizen.register.store'), [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('citizen.create'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
    }

    public function test_demo_citizen_account_can_sign_in(): void
    {
        User::factory()->create([
            'email' => 'citizen.demo@agos.test',
            'password' => 'Citizen@12345',
        ]);

        $this->post(route('citizen.login.store'), [
            'email' => 'citizen.demo@agos.test',
            'password' => 'Citizen@12345',
        ])->assertRedirect(route('citizen.create'));

        $this->assertAuthenticated();
    }

    public function test_citizen_login_does_not_use_an_admin_intended_url(): void
    {
        $citizen = User::factory()->create([
            'email' => 'citizen@example.com',
            'password' => 'Password123!',
        ]);

        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('citizen.login.store'), [
                'email' => $citizen->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('citizen.create'));
    }

    public function test_super_admin_cannot_sign_in_to_citizen_app(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'email' => 'superadmin@example.com',
            'password' => 'Password123!',
        ]);

        $this->post(route('citizen.login.store'), [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_barangay_admin_cannot_open_citizen_app(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
        ]);

        $this->actingAs($admin)->get(route('citizen.create'))->assertForbidden();
    }
}
