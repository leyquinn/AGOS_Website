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
            'name' => ['nullable', 'string', 'max:255', 'required_without:first_name'],
            'first_name' => ['nullable', 'string', 'max:255', 'required_without:name'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);

        [$firstName, $lastName] = $this->splitName($validated);

        $user = User::create([
            'role_id' => Role::firstOrCreate(
                ['name' => 'citizen'],
                ['description' => 'Registered disaster information contributor'],
            )->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? 'api-'.str()->uuid(),
            'password' => $validated['password'],
            'barangay_id' => $validated['barangay_id'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Registration submitted for Barangay Admin review.',
            'user' => $user->load(['role', 'barangay']),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with(['role', 'barangay'])->where('email', $credentials['email'])->first();

        if (! $user || ! Auth::guard('web')->validate($credentials) || ! $user->is_active) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        if (! $user->isCitizen()) {
            return response()->json([
                'message' => 'Only citizen accounts can access the Citizen App.',
            ], 403);
        }

        if (! $user->isApproved()) {
            $message = $user->barangay
                ? "Your registration for Barangay {$user->barangay->name} is under review by your Barangay Admin. You will receive an email notification once reviewed."
                : 'Your registration is under review by your Barangay Admin. You will receive an email notification once reviewed.';

            if ($user->status === 'declined') {
                $message = 'Your registration was declined. Please contact your barangay office for assistance.';
            }

            return response()->json([
                'message' => $message,
                'status' => $user->status,
                'rejection_reason' => $user->rejection_reason,
            ], 403);
        }

        return response()->json([
            'user' => $user,
            'access_token' => $user->createToken('citizen_mobile_app')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user()->load('barangay');

        return response()->json([
            'status' => $user->status,
            'barangay' => $user->barangay?->name,
            'rejection_reason' => $user->rejection_reason,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(array $validated): array
    {
        if (! empty($validated['name'])) {
            $parts = preg_split('/\s+/', trim($validated['name']), 2);

            return [$parts[0], $parts[1] ?? ''];
        }

        return [$validated['first_name'], $validated['last_name'] ?? ''];
    }
}
