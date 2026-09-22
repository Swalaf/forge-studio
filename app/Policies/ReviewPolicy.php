<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function view(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->customer_id === $user->id || $review->product->author_id === $user->id;
    }

    public function reply(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->product->author_id === $user->id;
    }
}
