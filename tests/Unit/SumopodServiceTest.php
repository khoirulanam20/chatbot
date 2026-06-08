<?php

namespace Tests\Unit;

use App\Services\SumopodService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SumopodServiceTest extends TestCase
{
    public function test_resolve_model_uses_global_config(): void
    {
        config(['services.sumopod.chat_model' => 'gpt-4o-mini']);

        $service = app(SumopodService::class);

        $this->assertSame('gpt-4o-mini', $service->resolveModel());
    }

    public function test_resolve_model_uses_tenant_override(): void
    {
        config(['services.sumopod.chat_model' => 'gpt-4o']);

        $service = app(SumopodService::class)->withTenantSettings([
            'ai_chat_model' => 'gpt-4o-mini',
        ]);

        $this->assertSame('gpt-4o-mini', $service->resolveModel());
    }

    public function test_resolve_model_normalizes_typo_gpt40_mini(): void
    {
        config(['services.sumopod.chat_model' => 'gpt-40-mini']);

        $service = app(SumopodService::class);

        $this->assertSame('gpt-4o-mini', $service->resolveModel());
    }

    public function test_resolve_model_explicit_override_wins(): void
    {
        config(['services.sumopod.chat_model' => 'gpt-4o']);

        $service = app(SumopodService::class);

        $this->assertSame('custom-model', $service->resolveModel(null, 'custom-model'));
    }

    public function test_resolve_model_throws_when_not_configured(): void
    {
        config(['services.sumopod.chat_model' => '']);

        $service = app(SumopodService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Model AI belum dikonfigurasi');

        $service->resolveModel();
    }

    public function test_validate_embed_model_detects_duplication(): void
    {
        $error = SumopodService::validateEmbedModelName('text-embedding-3-smalltext-embedding-3-small');

        $this->assertNotNull($error);
        $this->assertStringContainsString('terduplikasi', $error);
    }

    public function test_validate_embed_model_accepts_valid_name(): void
    {
        $this->assertNull(SumopodService::validateEmbedModelName('text-embedding-3-small'));
    }

    public function test_test_connection_returns_clear_message_when_api_key_missing(): void
    {
        config([
            'services.sumopod.api_key'     => '',
            'services.sumopod.base_url'    => 'https://ai.sumopod.com',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $result = app(SumopodService::class)->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('API key belum dikonfigurasi', $result['message']);
        $this->assertArrayHasKey('config', $result);
    }

    public function test_test_connection_succeeds_with_http_fake(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'pong']]],
                'usage'   => ['total_tokens' => 5],
                'model'   => 'gpt-4o-mini',
            ]),
            '*/embeddings' => Http::response([
                'data' => [['embedding' => [0.5]]],
            ]),
        ]);

        config([
            'services.sumopod.api_key'     => 'test-key',
            'services.sumopod.base_url'    => 'https://ai.sumopod.com',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        $result = app(SumopodService::class)->testConnection();

        $this->assertTrue($result['success']);
        $this->assertSame('Koneksi AI berhasil!', $result['message']);
        $this->assertTrue($result['checks']['chat']);
        $this->assertTrue($result['checks']['embedding']);
    }

    public function test_test_connection_succeeds_when_only_chat_works(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'pong']]],
                'usage'   => ['total_tokens' => 5],
                'model'   => 'claude-sonnet-4.5',
            ]),
            '*/embeddings' => Http::response(['error' => ['message' => 'not found']], 404),
        ]);

        config([
            'services.sumopod.api_key'     => 'test-key',
            'services.sumopod.base_url'    => 'https://openagentic.id/api/v1',
            'services.sumopod.embed_model' => 'claude-sonnet-4.5',
            'services.sumopod.chat_model'  => 'claude-sonnet-4.5',
        ]);

        $result = app(SumopodService::class)->testConnection();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Koneksi AI berhasil!', $result['message']);
        $this->assertStringContainsString('embedding', $result['message']);
        $this->assertTrue($result['checks']['chat']);
        $this->assertFalse($result['checks']['embedding']);
    }
}
