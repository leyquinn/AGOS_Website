<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function approve(User $admin, User $citizen): bool
    {
        return $this->canModerate($admin, $citizen);
    }

    public function decline(User $admin, User $citizen): bool
    {
        return $this->canModerate($admin, $citizen);
    }

    private function canModerate(User $admin, User $citizen): bool
    {
        return $admin->isBarangayAdmin()
            && $citizen->isCitizen()
            && $admin->belongsToBarangay($citizen->barangay_id);
    }
}
