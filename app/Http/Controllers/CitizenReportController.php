<?php

namespace App\Http\Controllers;

use App\Models\FloodReport;
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
        return view('citizen.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $nameParts = preg_split('/\s+/', trim($validated['name']), 2);

        $user = User::create([
            'role_id' => Role::firstOrCreate(
                ['name' => 'citizen'],
                ['description' => 'Registered disaster information contributor'],
            )->id,
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
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

        return redirect()->intended(route('citizen.create'));
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
        return view('citizen.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reporter_name' => ['nullable', 'string', 'max:120'],
            'reporter_contact' => ['nullable', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:255'],
            'water_level' => ['required', 'string', 'in:ankle,knee,waist,chest,roof,impassable'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        FloodReport::create($validated);

        return redirect()
            ->route('citizen.create')
            ->with('status', 'Your flood update was submitted for validation. Thank you for helping keep the community safe.');
    }
}
