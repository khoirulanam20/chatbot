<?php

namespace Tests\Unit;

use App\Services\SumopodService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SumopodServiceCacheTest extends TestCase
{
    public function test_chat_cache_isolated_per_api_credentials(): void
    {
        Http::fake([
            '*/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [['message' => ['content' => 'tenant-a']]],
                    'usage'   => ['total_tokens' => 1],
                    'model'   => 'gpt-4o-mini',
                ])
                ->push([
                    'choices' => [['message' => ['content' => 'tenant-b']]],
                    'usage'   => ['total_tokens' => 1],
                    'model'   => 'gpt-4o-mini',
                ]),
        ]);

        config([
            'services.sumopod.api_key'     => 'global-key',
            'services.sumopod.base_url'    => 'https://ai.example.com/v1',
            'services.sumopod.embed_model' => 'text-embedding-3-small',
            'services.sumopod.chat_model'  => 'gpt-4o-mini',
        ]);

        Cache::flush();

        $messages = [['role' => 'user', 'content' => 'same prompt']];

        $serviceA = app(SumopodService::class)->withTenantSettings(['ai_api_key' => 'key-a']);
        $serviceB = app(SumopodService::class)->withTenantSettings(['ai_api_key' => 'key-b']);

        $resultA = $serviceA->chat($messages);
        $resultB = $serviceB->chat($messages);

        $this->assertSame('tenant-a', $resultA['content']);
        $this->assertSame('tenant-b', $resultB['content']);

        Http::assertSentCount(2);
    }
}
