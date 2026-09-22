<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->customer_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
