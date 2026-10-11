<?php

namespace Tests\Feature;

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
            ->assertSee(route('citizen.login'));
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
}
