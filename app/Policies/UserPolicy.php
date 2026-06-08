<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() || $actor->tenant_id === $target->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        if ($target->role === 'super_admin' && ! $actor->isSuperAdmin()) {
            return false;
        }

        return $actor->isAdmin() && ($actor->isSuperAdmin() || $actor->tenant_id === $target->tenant_id);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target);
    }
}
