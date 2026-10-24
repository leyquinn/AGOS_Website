<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CitizenAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:255', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);

        $user = User::create([
            ...$validated,
            'role_id' => Role::firstOrCreate(
                ['name' => 'citizen'],
                ['description' => 'Registered disaster information contributor'],
            )->id,
        ]);

        return response()->json([
            'user' => $user->load(['role', 'barangay']),
            'access_token' => $user->createToken('citizen_mobile_app')->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true])) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isCitizen()) {
            Auth::logout();

            return response()->json([
                'message' => 'Only citizen accounts can access the Citizen App.',
            ], 403);
        }

        return response()->json([
            'user' => $user->load(['role', 'barangay']),
            'access_token' => $user->createToken('citizen_mobile_app')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
