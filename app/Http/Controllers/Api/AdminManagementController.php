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

        return User::create([
            ...$validated,
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
