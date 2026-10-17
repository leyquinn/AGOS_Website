<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_logout_returns_to_registration_and_expires_private_page_cache(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'citizen'])->id,
        ]);

        $privatePage = $this->actingAs($user)->get(route('citizen.create'));
        $this->assertStringContainsString('no-store', $privatePage->headers->get('Cache-Control'));

        $this->actingAs($user)->post(route('citizen.logout'))
            ->assertRedirect(route('citizen.register'));

        $this->assertGuest();
    }

    public function test_stale_citizen_logout_url_redirects_to_registration(): void
    {
        $this->get('/citizen/logout')->assertRedirect(route('citizen.register'));
    }

    public function test_admin_logout_returns_to_admin_login_and_expires_private_page_cache(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'password' => Hash::make('Password123!'),
        ]);

        $privatePage = $this->actingAs($user)->get(route('dashboard'));
        $this->assertStringContainsString('no-store', $privatePage->headers->get('Cache-Control'));

        $this->actingAs($user)->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_stale_admin_logout_url_redirects_to_admin_login(): void
    {
        $this->get('/logout')->assertRedirect(route('login'));
    }
}
