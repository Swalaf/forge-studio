<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->opener_id === $user->id
            || $ticket->assigned_to === $user->id
            || ($user->isAuthor() && $ticket->product?->author_id === $user->id);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $ticket->assigned_to === $user->id || ($user->isAuthor() && $ticket->product?->author_id === $user->id);
    }
}
