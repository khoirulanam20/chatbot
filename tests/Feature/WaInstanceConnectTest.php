<?php

namespace Tests\Feature;

use App\Models\WaInstance;
use App\Services\WaChateryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTestActors;
use Tests\TestCase;

class WaInstanceConnectTest extends TestCase
{
    use CreatesTestActors;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.chatery.base_url' => 'https://wa.test/api',
            'services.chatery.api_key'  => 'global-chatery-key',
        ]);
    }

    private function createWaInstance(string $status = 'connecting'): WaInstance
    {
        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin');
        $this->actingAs($admin);

        $chatbot = $this->createChatbot($tenant);

        return WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $tenant->id,
            'chatbot_id'   => $chatbot->id,
            'instance_id'  => WaChateryService::sessionIdForChatbot($chatbot->id),
            'phone_number' => null,
            'status'       => $status,
        ]);
    }

    public function test_store_creates_instance_and_redirects_to_connect(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin');
        $chatbot = $this->createChatbot($tenant);

        $response = $this->actingAs($admin)->post('/admin/wa', [
            'chatbot_id'         => $chatbot->id,
            'typing_enabled'     => true,
            'typing_duration_ms' => 2500,
        ]);

        $waInstance = WaInstance::withoutGlobalScopes()->first();

        $response->assertRedirect(route('admin.wa.connect', $waInstance));
        $this->assertSame('bot-' . $chatbot->id, $waInstance->instance_id);
        $this->assertSame('connecting', $waInstance->status);
        $this->assertNull($waInstance->phone_number);
        $this->assertTrue($waInstance->typing_enabled);
        $this->assertSame(2500, $waInstance->typing_duration_ms);
    }

    public function test_store_fails_when_api_key_missing(): void
    {
        config(['services.chatery.api_key' => '']);

        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin');
        $chatbot = $this->createChatbot($tenant);

        $response = $this->actingAs($admin)->from('/admin/wa/create')->post('/admin/wa', [
            'chatbot_id' => $chatbot->id,
        ]);

        $response->assertRedirect('/admin/wa/create');
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('wa_instances', 0);
    }

    public function test_store_fails_when_chatbot_already_has_instance(): void
    {
        $tenant = $this->createTenant();
        $admin = $this->createUser($tenant, 'admin');
        $chatbot = $this->createChatbot($tenant);

        WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $tenant->id,
            'chatbot_id'   => $chatbot->id,
            'instance_id'  => 'bot-' . $chatbot->id,
            'phone_number' => '628123',
            'status'       => 'active',
        ]);

        $response = $this->actingAs($admin)->from('/admin/wa/create')->post('/admin/wa', [
            'chatbot_id' => $chatbot->id,
        ]);

        $response->assertRedirect('/admin/wa/create');
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('wa_instances', 1);
    }

    public function test_initiate_connect_calls_chatery_with_webhook(): void
    {
        Http::fake([
            'wa.test/api/whatsapp/sessions/bot-*/connect' => Http::response([
                'data' => [
                    'status' => 'qr_ready',
                ],
            ], 200),
        ]);

        $waInstance = $this->createWaInstance();

        $response = $this->post(route('admin.wa.connect.initiate', $waInstance));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) use ($waInstance) {
            if (! str_contains($request->url(), '/whatsapp/sessions/' . $waInstance->instance_id . '/connect')) {
                return false;
            }

            $body = $request->data();

            return ($request->header('X-Api-Key')[0] ?? null) === 'global-chatery-key'
                && ($body['webhooks'][0]['url'] ?? '') === url('/api/webhook/whatsapp')
                && ($body['webhooks'][0]['events'][0] ?? '') === 'all';
        });

        $waInstance->refresh();
        $this->assertSame('connecting', $waInstance->status);
    }

    public function test_qr_endpoint_returns_png(): void
    {
        Http::fake([
            'wa.test/api/whatsapp/sessions/bot-*/qr/image' => Http::response('fake-png-bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $waInstance = $this->createWaInstance();

        $response = $this->get(route('admin.wa.qr', $waInstance));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame('fake-png-bytes', $response->getContent());
    }

    public function test_status_endpoint_updates_instance_when_connected(): void
    {
        Http::fake([
            'wa.test/api/whatsapp/sessions/bot-*/status' => Http::response([
                'data' => [
                    'status'      => 'connected',
                    'phoneNumber' => '6285117742328',
                    'isConnected' => true,
                ],
            ], 200),
        ]);

        $waInstance = $this->createWaInstance();

        $response = $this->getJson(route('admin.wa.status', $waInstance));

        $response->assertOk()
            ->assertJson([
                'success'      => true,
                'status'       => 'connected',
                'is_connected' => true,
                'local_status' => 'active',
                'phone_number' => '6285117742328',
            ]);

        $waInstance->refresh();
        $this->assertSame('active', $waInstance->status);
        $this->assertSame('6285117742328', $waInstance->phone_number);
    }

    public function test_connect_page_renders_inertia_component(): void
    {
        Http::fake([
            'wa.test/api/whatsapp/sessions/bot-*/status' => Http::response([
                'data' => [
                    'status'      => 'qr_ready',
                    'isConnected' => false,
                ],
            ], 200),
        ]);

        $waInstance = $this->createWaInstance();

        $response = $this->get(route('admin.wa.connect', $waInstance));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('wa/Connect')
                ->where('waInstance.id', $waInstance->id)
                ->where('hasApiKey', true));
    }
}
