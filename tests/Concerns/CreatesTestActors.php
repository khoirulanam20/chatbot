<?php

namespace Tests\Concerns;

use App\Models\BotEmbedConfig;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait CreatesTestActors
{
    protected function createTenant(string $name = 'Test Tenant', string $slug = 'test-tenant'): Tenant
    {
        return Tenant::create([
            'name'      => $name,
            'slug'      => $slug,
            'plan'      => 'pro',
            'is_active' => true,
            'settings'  => [],
        ]);
    }

    protected function createUser(Tenant $tenant, string $role = 'admin', string $email = 'user@test.test'): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name'      => ucfirst($role) . ' User',
            'email'     => $email,
            'password'  => Hash::make('password'),
            'role'      => $role,
        ]);
    }

    protected function createChatbot(Tenant $tenant, string $name = 'Bot'): Chatbot
    {
        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'        => $tenant->id,
            'name'             => $name,
            'system_prompt'    => 'Test prompt',
            'temperature'      => 0.7,
            'max_context'      => 5,
            'language'         => 'id',
            'fallback_message' => 'Fallback',
            'is_active'        => true,
            'settings'         => [],
        ]);

        BotEmbedConfig::create([
            'chatbot_id'    => $chatbot->id,
            'primary_color' => '#000000',
            'position'      => 'bottom-right',
            'greeting'      => 'Halo',
        ]);

        return $chatbot;
    }
}
