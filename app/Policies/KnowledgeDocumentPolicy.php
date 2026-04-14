<?php

namespace App\Policies;

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
        return $user->isSuperAdmin() || $user->tenant_id === $document->chatbot->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, KnowledgeDocument $document): bool
    {
        return $user->isAdmin() && ($user->isSuperAdmin() || $user->tenant_id === $document->chatbot->tenant_id);
    }
}
