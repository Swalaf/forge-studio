<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin() || $product->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAuthor();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $product->author_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $product->author_id === $user->id;
    }
}
