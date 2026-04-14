<?php

namespace App\Policies;

use App\Models\Chatbot;
use App\Models\User;

class ChatbotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOperator();
    }

    public function view(User $user, Chatbot $chatbot): bool
    {
        return $user->isSuperAdmin() || $user->tenant_id === $chatbot->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Chatbot $chatbot): bool
    {
        return $user->isAdmin() && ($user->isSuperAdmin() || $user->tenant_id === $chatbot->tenant_id);
    }

    public function delete(User $user, Chatbot $chatbot): bool
    {
        return $user->isAdmin() && ($user->isSuperAdmin() || $user->tenant_id === $chatbot->tenant_id);
    }
}
