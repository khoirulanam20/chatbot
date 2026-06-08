<?php

namespace App\Providers;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\KnowledgeDocument;
use App\Policies\ChatbotPolicy;
use App\Policies\ConversationPolicy;
use App\Models\User;
use App\Policies\KnowledgeDocumentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Chatbot::class, ChatbotPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(KnowledgeDocument::class, KnowledgeDocumentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Horizon::auth(function ($request) {
            return $request->user()?->isSuperAdmin() ?? false;
        });
    }
}
