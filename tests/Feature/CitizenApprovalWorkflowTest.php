<?php

namespace Tests\Feature;

use App\Mail\CitizenAccountApprovedMail;
use App\Mail\CitizenAccountDeclinedMail;
use App\Models\Barangay;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CitizenApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_pending_and_does_not_issue_a_token(): void
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);

        $response = $this->postJson('/api/v1/auth/citizen/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'barangay_id' => $barangay->id,
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('access_token')
            ->assertJsonPath('user.status', 'pending');
    }

    public function test_pending_login_returns_barangay_review_message(): void
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $citizen = $this->createCitizen($barangay, ['status' => 'pending']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Your registration for Barangay Poblacion is under review by your Barangay Admin. You will receive an email notification once reviewed.');
    }

    public function test_barangay_admin_can_approve_only_citizens_in_their_barangay(): void
    {
        Mail::fake();
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $otherBarangay = Barangay::create(['name' => 'Toril', 'district' => '3rd District']);
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
            'barangay_id' => $barangay->id,
            'status' => 'approved',
        ]);
        $citizen = $this->createCitizen($barangay);
        $otherCitizen = $this->createCitizen($otherBarangay);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/citizens/{$citizen->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $citizen->id, 'status' => 'approved']);
        Mail::assertQueued(CitizenAccountApprovedMail::class);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/citizens/{$otherCitizen->id}/approve")
            ->assertForbidden();
    }

    public function test_decline_stores_reason_and_queues_notification(): void
    {
        Mail::fake();
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
            'barangay_id' => $barangay->id,
            'status' => 'approved',
        ]);
        $citizen = $this->createCitizen($barangay);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/citizens/{$citizen->id}/decline", [
                'rejection_reason' => 'Barangay assignment could not be verified.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $citizen->id,
            'status' => 'declined',
            'rejection_reason' => 'Barangay assignment could not be verified.',
        ]);
        Mail::assertQueued(CitizenAccountDeclinedMail::class);
    }

    public function test_barangay_admin_has_a_web_page_for_pending_approvals(): void
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
            'barangay_id' => $barangay->id,
            'status' => 'approved',
        ]);
        $citizen = $this->createCitizen($barangay);

        $this->actingAs($admin)
            ->get(route('admin.citizen-approvals'))
            ->assertOk()
            ->assertSee($citizen->email)
            ->assertSee('Approve')
            ->assertSee('Decline');
    }

    public function test_web_approval_updates_status_and_redirects_to_pending_page(): void
    {
        Mail::fake();
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
            'barangay_id' => $barangay->id,
            'status' => 'approved',
        ]);
        $citizen = $this->createCitizen($barangay);

        $this->actingAs($admin)
            ->post(route('admin.citizen-approvals.approve', $citizen))
            ->assertRedirect(route('admin.citizen-approvals'));

        $this->assertDatabaseHas('users', ['id' => $citizen->id, 'status' => 'approved']);
        Mail::assertQueued(CitizenAccountApprovedMail::class);
    }

    public function test_web_approval_form_contains_a_csrf_token(): void
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
            'barangay_id' => $barangay->id,
            'status' => 'approved',
        ]);
        $citizen = $this->createCitizen($barangay);

        $this->actingAs($admin)
            ->get(route('admin.citizen-approvals'))
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee(route('admin.citizen-approvals.approve', $citizen), false);
    }

    public function test_admin_can_change_approved_or_declined_status_later(): void
    {
        Mail::fake();
        $barangay = Barangay::create(['name' => 'Poblacion', 'district' => '1st District']);
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'barangay_admin'])->id,
            'barangay_id' => $barangay->id,
            'status' => 'approved',
        ]);
        $citizen = $this->createCitizen($barangay, [
            'status' => 'approved',
            'rejection_reason' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.citizen-approvals.decline', $citizen), [
                'rejection_reason' => 'Review changed.',
            ])
            ->assertRedirect(route('admin.citizen-approvals'));

        $this->assertDatabaseHas('users', [
            'id' => $citizen->id,
            'status' => 'declined',
            'rejection_reason' => 'Review changed.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.citizen-approvals.approve', $citizen))
            ->assertRedirect(route('admin.citizen-approvals'));

        $this->assertDatabaseHas('users', [
            'id' => $citizen->id,
            'status' => 'approved',
            'rejection_reason' => null,
        ]);
        Mail::assertQueued(CitizenAccountDeclinedMail::class);
        Mail::assertQueued(CitizenAccountApprovedMail::class);
    }

    private function createCitizen(Barangay $barangay, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => 'citizen'])->id,
            'barangay_id' => $barangay->id,
            'password' => Hash::make('password'),
            'status' => 'pending',
        ], $attributes));
    }
}
