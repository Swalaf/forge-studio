<?php

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    public function view(User $user, License $license): bool
    {
        return $user->isAdmin() || $license->customer_id === $user->id;
    }

    public function update(User $user, License $license): bool
    {
        return $user->isAdmin() || $license->customer_id === $user->id;
    }
}
