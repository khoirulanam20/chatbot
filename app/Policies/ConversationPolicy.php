<?php

namespace App\Policies;

use App\Models\Chatbot;
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
        if ($user->isSuperAdmin()) {
            return true;
        }

        $tenantId = $this->resolveTenantId($conversation);

        return $tenantId !== null && $user->tenant_id === $tenantId;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $tenantId = $this->resolveTenantId($conversation);

        return $tenantId !== null
            && $user->isOperator()
            && $user->tenant_id === $tenantId;
    }

    public function respond(User $user, Conversation $conversation): bool
    {
        return $this->update($user, $conversation);
    }

    private function resolveTenantId(Conversation $conversation): ?int
    {
        return Chatbot::withoutGlobalScopes()
            ->whereKey($conversation->chatbot_id)
            ->value('tenant_id');
    }
}
