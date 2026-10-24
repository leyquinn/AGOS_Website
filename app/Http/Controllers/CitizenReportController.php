<?php

namespace App\Http\Controllers;

use App\Models\CitizenReport;
use App\Models\Barangay;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CitizenReportController extends Controller
{
    public function showLogin(): View
    {
        return view('citizen.auth.login');
    }

    public function showRegistration(): View
    {
        return view('citizen.auth.register', ['barangays' => Barangay::orderBy('name')->get()]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);

        $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

        $user = User::create([
            'role_id' => Role::firstOrCreate(
                ['name' => 'citizen'],
                ['description' => 'Registered disaster information contributor'],
            )->id,
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'barangay_id' => $validated['barangay_id'],
            'email' => $validated['email'],
            'phone_number' => 'web-'.str()->uuid(),
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('citizen.create');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        if (! Auth::user()->isCitizen()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Only citizen accounts can access the Citizen App.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('citizen.create');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('citizen.register');
    }

    public function create(): View
    {
        $user = request()->user()->load('barangay');

        return view('citizen.create', [
            'barangays' => Barangay::orderBy('name')->get(),
            'citizenReports' => $user->citizenReports()->with('barangay')->latest()->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isCitizen(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);
        $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

        $request->user()->update([
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'barangay_id' => $validated['barangay_id'],
        ]);

        return to_route('citizen.create')->with('status', 'Your profile and assigned barangay were updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isCitizen(), 403);

        $validated = $request->validate([
            'reporter_name' => ['nullable', 'string', 'max:120'],
            'reporter_contact' => ['nullable', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:255'],
            'water_level' => ['required', 'string', 'in:ankle,knee,waist,chest,roof,impassable'],
            'description' => ['required', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $waterLevelMap = [
            'ankle' => 'low',
            'knee' => 'knee_deep',
            'waist' => 'waist_deep',
            'chest' => 'chest_deep',
            'roof' => 'roof_top',
            'impassable' => 'roof_top',
        ];

        CitizenReport::create([
            'user_id' => $request->user()->id,
            'barangay_id' => $request->user()->barangay_id,
            'report_text' => $validated['description'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'water_level_status' => $waterLevelMap[$validated['water_level']],
        ]);

        return redirect()
            ->route('citizen.create')
            ->with('status', 'Your flood update was submitted for validation. Thank you for helping keep the community safe.');
    }
}
