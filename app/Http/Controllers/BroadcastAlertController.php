<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\BroadcastAlert;
use App\Models\SocialPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BroadcastAlertController extends Controller
{
    /**
     * Store a newly broadcast alert from the Operations portal.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'severity' => ['required', 'string', 'in:CRITICAL,WARNING,ADVISORY,INFO'],
            'barangay_id' => ['nullable'],
            'social_post_id' => ['nullable', 'exists:social_posts,id'],
        ]);

        $barangayId = null;
        if (! empty($validated['barangay_id'])) {
            // Check if it is an ID or a name
            if (is_numeric($validated['barangay_id'])) {
                $barangay = Barangay::find($validated['barangay_id']);
                $barangayId = $barangay?->id;
            } else {
                $barangay = Barangay::where('name', $validated['barangay_id'])->first();
                $barangayId = $barangay?->id;
            }
        }

        $user = $request->user() ?: Auth::user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $alert = BroadcastAlert::create([
            'user_id' => $user->id,
            'barangay_id' => $barangayId,
            'social_post_id' => $validated['social_post_id'] ?? null,
            'title' => trim($validated['title']),
            'message' => trim($validated['message']),
            'severity' => $validated['severity'],
            'is_active' => true,
        ]);

        Log::info('Broadcast alert created', [
            'id' => $alert->id,
            'title' => $alert->title,
            'severity' => $alert->severity,
            'barangay_id' => $alert->barangay_id,
            'user_id' => $alert->user_id,
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alert successfully broadcast to citizen response channels.',
                'alert' => [
                    'id' => $alert->id,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'severity' => $alert->severity,
                    'barangay' => $alert->barangay?->name ?? 'Citywide',
                    'created_at' => $alert->created_at->toIso8601String(),
                ],
            ]);
        }

        return back()->with('status', 'Alert successfully broadcast to citizen response channels.');
    }

    /**
     * API endpoint returning active broadcast alerts for citizen devices.
     */
    public function citizenAlerts(Request $request): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $barangayId = $user?->barangay_id;

        $alerts = BroadcastAlert::with(['barangay', 'user'])
            ->where('is_active', true)
            ->where(function ($q) use ($barangayId) {
                $q->whereNull('barangay_id');
                if ($barangayId) {
                    $q->orWhere('barangay_id', $barangayId);
                }
            })
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'alerts' => $alerts->map(fn (BroadcastAlert $alert): array => [
                'id' => $alert->id,
                'title' => $alert->title,
                'message' => $alert->message,
                'severity' => $alert->severity,
                'barangay' => $alert->barangay?->name ?? 'Citywide',
                'target_scope' => $alert->barangay ? 'Brgy. ' . $alert->barangay->name : 'Davao City (Citywide)',
                'created_at' => $alert->created_at->toIso8601String(),
                'time_ago' => $alert->created_at->diffForHumans(),
            ]),
        ]);
    }
}
