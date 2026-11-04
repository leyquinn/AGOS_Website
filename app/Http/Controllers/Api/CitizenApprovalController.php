<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CitizenAccountApprovedMail;
use App\Mail\CitizenAccountDeclinedMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CitizenApprovalController extends Controller
{
    public function pendingPage(Request $request): View
    {
        return view('admin.citizen-approvals', [
            'citizens' => $this->citizensForStatus($request, 'pending'),
            'approvedCitizens' => $this->citizensForStatus($request, 'approved'),
            'declinedCitizens' => $this->citizensForStatus($request, 'declined'),
        ]);
    }

    private function citizensForStatus(Request $request, string $status)
    {
        return User::with('barangay')
                ->where('status', $status)
                ->where('barangay_id', $request->user()->barangay_id)
                ->whereRelation('role', 'name', 'citizen')
                ->oldest()
                ->get();
    }

    public function approveWeb(Request $request, User $user): RedirectResponse
    {
        $this->changeStatus($request, $user, 'approved');

        return to_route('admin.citizen-approvals')
            ->with('success', "{$user->first_name} {$user->last_name}'s account was approved.");
    }

    public function declineWeb(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->changeStatus($request, $user, 'declined', $validated['rejection_reason'] ?? null);

        return to_route('admin.citizen-approvals')
            ->with('success', "{$user->first_name} {$user->last_name}'s account was declined.");
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => User::with('barangay')
                ->where('status', 'pending')
                ->where('barangay_id', $request->user()->barangay_id)
                ->whereRelation('role', 'name', 'citizen')
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    public function approve(Request $request, User $user): JsonResponse
    {
        $this->changeStatus($request, $user, 'approved');

        return response()->json([
            'message' => 'Citizen account approved.',
            'user' => $user->fresh(['barangay', 'role']),
        ]);
    }

    public function decline(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->changeStatus($request, $user, 'declined', $validated['rejection_reason'] ?? null);

        return response()->json([
            'message' => 'Citizen account declined.',
            'user' => $user->fresh(['barangay', 'role']),
        ]);
    }

    private function changeStatus(Request $request, User $user, string $status, ?string $rejectionReason = null): void
    {
        Gate::authorize($status === 'approved' ? 'approve' : 'decline', $user);

        $user->forceFill([
            'status' => $status,
            'rejection_reason' => $status === 'approved' ? null : $rejectionReason,
        ])->save();

        if ($status === 'approved') {
            Mail::to($user)->queue(new CitizenAccountApprovedMail($user));
        } else {
            Mail::to($user)->queue(new CitizenAccountDeclinedMail($user));
        }
    }
}
