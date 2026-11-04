<h1>AGOS registration update</h1>

<p>Hello {{ $user->first_name }},</p>

<p>Your AGOS citizen registration for {{ $user->barangay?->name }} was declined.</p>

@if ($user->rejection_reason)
    <p>Reason: {{ $user->rejection_reason }}</p>
@endif

<p>Please contact your barangay office for assistance or to clarify your registration.</p>
