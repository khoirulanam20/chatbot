<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOperator();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->tenant_id === $conversation->chatbot->tenant_id;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->isOperator() && $user->tenant_id === $conversation->chatbot->tenant_id;
    }

    public function respond(User $user, Conversation $conversation): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->isOperator() && $user->tenant_id === $conversation->chatbot->tenant_id;
    }
}
