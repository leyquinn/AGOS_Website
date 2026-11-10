<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    public function barangays(): View
    {
        return view('admin.barangays', [
            'barangays' => Barangay::with(['users' => fn ($query) => $query
                ->whereRelation('role', 'name', 'barangay_admin')
                ->orderBy('first_name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function assignBarangayAdminWeb(Request $request): RedirectResponse
    {
        $this->createBarangayAdmin($request);

        return to_route('admin.barangays')
            ->with('success', 'Barangay admin assigned successfully.');
    }

    public function revokeBarangayAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Unauthorized access for your role level.');
        abort_unless($user->isBarangayAdmin(), 404);

        $user->forceFill([
            'status' => 'revoked',
            'is_active' => false,
        ])->save();
        $user->tokens()->delete();

        return to_route('admin.barangays')
            ->with('success', "{$user->first_name} {$user->last_name}'s access was revoked.");
    }

    public function assignBarangayAdminAtBarangay(Request $request): RedirectResponse
    {
        return $this->assignBarangayAdminWeb($request);
    }

    public function assignBarangayAdmin(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Unauthorized access for your role level.');

        $admin = $this->createBarangayAdmin($request);

        return response()->json([
            'message' => 'Barangay admin assigned successfully.',
            'admin' => $admin->load('barangay'),
        ], 201);
    }

    private function createBarangayAdmin(Request $request): User
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:255', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);

        User::query()
            ->where('barangay_id', $validated['barangay_id'])
            ->where('is_active', true)
            ->where('status', 'approved')
            ->whereRelation('role', 'name', 'barangay_admin')
            ->get()
            ->each(function (User $admin): void {
                $admin->forceFill([
                    'status' => 'revoked',
                    'is_active' => false,
                ])->save();
                $admin->tokens()->delete();
            });

        return User::create([
            ...$validated,
            'is_active' => true,
            'status' => 'approved',
            'role_id' => Role::firstOrCreate(
                ['name' => 'barangay_admin'],
                ['description' => 'Administrator assigned to one barangay'],
            )->id,
        ]);
    }

    public function listBarangayAdmins(): JsonResponse
    {
        return response()->json(
            User::with('barangay')->whereRelation('role', 'name', 'barangay_admin')->get()
        );
    }

    public function listBarangays(): JsonResponse
    {
        return response()->json(Barangay::orderBy('name')->get());
    }
}
