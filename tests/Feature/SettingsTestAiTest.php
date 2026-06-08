<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsTestAiTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $tenant = Tenant::create([
            'name'      => 'Test Tenant',
            'slug'      => 'test-tenant',
            'plan'      => 'pro',
            'is_active' => true,
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Test',
            'email'     => 'admin@test.test',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
        ]);
    }

    public function test_test_ai_fails_with_clear_message_when_api_key_missing(): void
    {
        config([
            'services.sumopod.api_key'     => '',
            'services.sumopod.base_url'    => 'https://ai.sumopod.com',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->postJson('/admin/settings/test-ai', []);

        $response->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'API key belum dikonfigurasi. Isi API key tenant atau minta superadmin mengatur global.',
            ]);
    }

    public function test_test_ai_detects_duplicated_embed_model(): void
    {
        config([
            'services.sumopod.api_key'     => 'test-key',
            'services.sumopod.base_url'    => 'https://ai.sumopod.com',
            'services.sumopod.embed_model' => 'text-embedding-3-smalltext-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->postJson('/admin/settings/test-ai', []);

        $response->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn (string $msg) => str_contains($msg, 'terduplikasi'));
    }

    public function test_test_ai_succeeds_with_valid_config(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'pong']]],
                'usage'   => ['total_tokens' => 5],
                'model'   => 'gpt-4o-mini',
            ]),
            '*/embeddings' => Http::response([
                'data' => [['embedding' => [0.1, 0.2]]],
            ]),
        ]);

        config([
            'services.sumopod.api_key'     => 'test-key',
            'services.sumopod.base_url'    => 'https://ai.sumopod.com',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->postJson('/admin/settings/test-ai', []);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Koneksi AI berhasil!',
            ]);
    }

    public function test_test_ai_uses_form_api_key_override(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'pong']]],
                'usage'   => ['total_tokens' => 5],
                'model'   => 'gpt-4o-mini',
            ]),
            '*/embeddings' => Http::response([
                'data' => [['embedding' => [0.1]]],
            ]),
        ]);

        config([
            'services.sumopod.api_key'     => '',
            'services.sumopod.base_url'    => 'https://ai.sumopod.com',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->postJson('/admin/settings/test-ai', [
                'ai_api_key' => 'form-override-key',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer form-override-key')
                && str_contains($request->url(), '/chat/completions');
        });
    }
}
