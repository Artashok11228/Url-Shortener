<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Link;
use App\Models\User;

class LinkPolicy
{
    /**
     * Admins pass every ability. Returning null lets the named method decide for others.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Link $link): bool
    {
        return $link->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Link $link): bool
    {
        return $link->user_id === $user->id;
    }

    public function delete(User $user, Link $link): bool
    {
        return $link->user_id === $user->id;
    }
}
