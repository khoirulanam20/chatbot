<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\WaInstance;
use App\Services\WaChateryService;
use Illuminate\Http\Request;

class WaInstanceController extends Controller
{
    public function __construct(
        private WaChateryService $chatery
    ) {}

    public function index()
    {
        $instances = WaInstance::with(['tenant', 'chatbot'])->paginate(15);

        return inertia('wa/Index', ['instances' => $instances]);
    }

    public function create()
    {
        $chatbots = Chatbot::with('waInstance')
            ->get()
            ->filter(fn (Chatbot $chatbot) => ! $chatbot->waInstance);

        return inertia('wa/Create', [
            'chatbots'  => $chatbots->values(),
            'hasApiKey' => filled($this->chatery->resolveApiKey()),
        ]);
    }

    public function store(Request $request)
    {
        if (! filled($this->chatery->resolveApiKey())) {
            return back()
                ->withInput()
                ->with('error', 'CHATERY_API_KEY belum dikonfigurasi di .env.');
        }

        $request->validate([
            'chatbot_id'         => 'required|exists:chatbots,id',
            'typing_enabled'     => 'nullable|boolean',
            'typing_duration_ms' => 'nullable|integer|min:500|max:10000',
        ]);

        $chatbot = Chatbot::with('waInstance')->findOrFail($request->chatbot_id);

        if ($chatbot->waInstance) {
            return back()
                ->withInput()
                ->with('error', 'Chatbot ini sudah memiliki instance WhatsApp.');
        }

        $instanceId = WaChateryService::sessionIdForChatbot($chatbot->id);

        $waInstance = WaInstance::withoutGlobalScopes()->create([
            'tenant_id'          => $chatbot->tenant_id,
            'chatbot_id'         => $chatbot->id,
            'phone_number'       => null,
            'instance_id'        => $instanceId,
            'status'             => 'connecting',
            'typing_enabled'     => $request->boolean('typing_enabled'),
            'typing_duration_ms' => $request->input('typing_duration_ms', 2000),
        ]);

        return redirect()
            ->route('admin.wa.connect', $waInstance)
            ->with('success', 'Instance dibuat. Scan QR code untuk menghubungkan WhatsApp.');
    }

    public function connect(WaInstance $waInstance)
    {
        $waInstance->load('chatbot');

        $apiKey = $this->chatery->resolveApiKey($waInstance);
        $connectionStatus = null;

        if ($apiKey && $waInstance->instance_id) {
            $connectionStatus = $this->chatery->getSessionStatus($apiKey, $waInstance->instance_id);
            $this->syncInstanceFromChateryStatus($waInstance, $connectionStatus);
            $waInstance->refresh();
        }

        return inertia('wa/Connect', [
            'waInstance'       => $waInstance,
            'hasApiKey'        => filled($apiKey),
            'connectionStatus' => $connectionStatus,
        ]);
    }

    public function initiateConnect(WaInstance $waInstance)
    {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey)) {
            return back()->with('error', 'CHATERY_API_KEY belum dikonfigurasi di .env.');
        }

        if (! $waInstance->instance_id) {
            return back()->with('error', 'Instance ID belum tersedia.');
        }

        $result = $this->chatery->connectSession($apiKey, $waInstance->instance_id);

        if (! $result['success']) {
            $waInstance->update([
                'status'   => 'error',
                'metadata' => array_merge($waInstance->metadata ?? [], [
                    'last_error'     => $result['error'] ?? 'Gagal menghubungkan ke Chatery.',
                    'last_tested_at' => now()->toIso8601String(),
                ]),
            ]);

            return back()->with('error', 'Gagal menghubungkan: ' . ($result['error'] ?? 'Unknown error'));
        }

        $waInstance->update([
            'status'   => WaChateryService::mapChateryStatusToLocal($result['status']),
            'metadata' => array_merge($waInstance->metadata ?? [], [
                'last_error'      => null,
                'chatery_status'  => $result['status'],
                'last_connected_at' => now()->toIso8601String(),
            ]),
        ]);

        return back()->with('success', 'Permintaan koneksi dikirim. Scan QR code di bawah.');
    }

    public function qr(WaInstance $waInstance)
    {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey) || ! $waInstance->instance_id) {
            abort(404);
        }

        $image = $this->chatery->getQrImage($apiKey, $waInstance->instance_id);

        if ($image === null) {
            abort(404);
        }

        return response($image, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function status(WaInstance $waInstance)
    {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey) || ! $waInstance->instance_id) {
            return response()->json([
                'success' => false,
                'error'   => 'Instance atau API key tidak tersedia.',
            ], 422);
        }

        $result = $this->chatery->getSessionStatus($apiKey, $waInstance->instance_id);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'] ?? 'Gagal memuat status.',
            ], 502);
        }

        $this->syncInstanceFromChateryStatus($waInstance, $result);
        $waInstance->refresh();

        return response()->json([
            'success'      => true,
            'status'       => $result['status'],
            'phone'        => $result['phone'],
            'is_connected' => $result['is_connected'],
            'local_status' => $waInstance->status,
            'phone_number' => $waInstance->phone_number,
        ]);
    }

    public function edit(WaInstance $waInstance)
    {
        $chatbots = Chatbot::all();
        return inertia('wa/Edit', [
            'waInstance' => $waInstance->load('chatbot'),
            'chatbots'   => $chatbots,
            'hasApiKey'  => filled($this->chatery->resolveApiKey($waInstance)),
        ]);
    }

    public function update(Request $request, WaInstance $waInstance)
    {
        $request->validate([
            'chatbot_id'         => 'required|exists:chatbots,id',
            'typing_enabled'     => 'nullable|boolean',
            'typing_duration_ms' => 'nullable|integer|min:500|max:10000',
        ]);

        $waInstance->update([
            'chatbot_id'         => $request->chatbot_id,
            'typing_enabled'     => $request->boolean('typing_enabled'),
            'typing_duration_ms' => $request->input('typing_duration_ms', $waInstance->typing_duration_ms ?? 2000),
        ]);

        return back()->with('success', 'WA Instance berhasil diperbarui.');
    }

    public function testConnection(WaInstance $waInstance)
    {
        $apiKey = $this->chatery->resolveApiKey($waInstance);

        if (! filled($apiKey)) {
            return back()->with('error', 'CHATERY_API_KEY belum dikonfigurasi di .env.');
        }

        $instanceId = $waInstance->instance_id ?: 'default';
        $result     = $this->chatery->testConnection($apiKey, $instanceId);

        if ($result['success']) {
            $phone = $result['phone'] ? WaChateryService::normalizePhone($result['phone']) : $waInstance->phone_number;

            $waInstance->update([
                'status'       => WaChateryService::mapChateryStatusToLocal($result['status'], ($result['status'] ?? '') === 'connected'),
                'phone_number' => $phone,
                'metadata'     => array_merge($waInstance->metadata ?? [], [
                    'last_error'     => null,
                    'last_tested_at' => now()->toIso8601String(),
                    'last_test_ok'   => true,
                    'chatery_status' => $result['status'] ?? 'connected',
                    'chatery_phone'  => $result['phone'] ?? null,
                ]),
            ]);

            return back()->with('success', 'Koneksi berhasil! Status Chatery: ' . ($result['status'] ?? 'connected'));
        }

        $errorMessage = $result['error'] ?? 'Unknown error';

        $waInstance->update([
            'status'   => 'error',
            'metadata' => array_merge($waInstance->metadata ?? [], [
                'last_error'     => $errorMessage,
                'last_tested_at' => now()->toIso8601String(),
                'last_test_ok'   => false,
            ]),
        ]);

        return back()
            ->with('error', 'Koneksi gagal: ' . $errorMessage)
            ->withErrors(['connection' => 'Koneksi gagal: ' . $errorMessage]);
    }

    public function destroy(WaInstance $waInstance)
    {
        $waInstance->delete();

        return back()->with('success', 'WA Instance berhasil dihapus.');
    }

    private function syncInstanceFromChateryStatus(WaInstance $waInstance, array $result): void
    {
        if (! ($result['success'] ?? false)) {
            return;
        }

        $chateryStatus = $result['status'] ?? null;
        $localStatus   = WaChateryService::mapChateryStatusToLocal($chateryStatus, $result['is_connected'] ?? false);

        $updates = [
            'status'   => $localStatus,
            'metadata' => array_merge($waInstance->metadata ?? [], [
                'chatery_status' => $chateryStatus,
                'chatery_phone'  => $result['phone'] ?? null,
                'last_synced_at' => now()->toIso8601String(),
            ]),
        ];

        if (($result['is_connected'] ?? false) && filled($result['phone'])) {
            $updates['phone_number'] = WaChateryService::normalizePhone($result['phone']);
            $updates['metadata']['last_error'] = null;
        }

        $waInstance->update($updates);
    }
}
