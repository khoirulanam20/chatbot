<?php

namespace App\Policies;

use App\Models\Chatbot;
use App\Models\KnowledgeDocument;
use App\Models\User;

class KnowledgeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOperator();
    }

    public function view(User $user, KnowledgeDocument $document): bool
    {
        $tenantId = $this->resolveTenantId($document);

        return $tenantId !== null
            && ($user->isSuperAdmin() || $user->tenant_id === $tenantId);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, KnowledgeDocument $document): bool
    {
        $tenantId = $this->resolveTenantId($document);

        return $tenantId !== null
            && $user->isAdmin()
            && ($user->isSuperAdmin() || $user->tenant_id === $tenantId);
    }

    public function delete(User $user, KnowledgeDocument $document): bool
    {
        return $this->update($user, $document);
    }

    private function resolveTenantId(KnowledgeDocument $document): ?int
    {
        return Chatbot::withoutGlobalScopes()
            ->whereKey($document->chatbot_id)
            ->value('tenant_id');
    }
}
