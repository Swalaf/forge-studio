<?php

namespace App\Policies;

use App\Models\ServiceProject;
use App\Models\User;

class ServiceProjectPolicy
{
    public function view(User $user, ServiceProject $project): bool
    {
        return $user->isAdmin() || $project->customer_id === $user->id || $project->owner_id === $user->id;
    }

    public function update(User $user, ServiceProject $project): bool
    {
        return $user->isAdmin() || $project->owner_id === $user->id;
    }
}
