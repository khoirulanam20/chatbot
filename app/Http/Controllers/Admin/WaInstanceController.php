<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\WaInstance;
use App\Services\WaChateryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $chatbots = Chatbot::all();

        return inertia('wa/Create', [
            'chatbots'   => $chatbots,
            'webhookUrl' => $this->chatery->getWebhookUrl(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'chatbot_id'   => 'required|exists:chatbots,id',
            'phone_number' => 'required|string|max:20',
            'api_key'      => 'required|string',
            'instance_id'        => 'nullable|string|max:100',
            'typing_enabled'     => 'nullable|boolean',
            'typing_duration_ms' => 'nullable|integer|min:500|max:10000',
        ]);

        $chatbot = Chatbot::findOrFail($request->chatbot_id);

        WaInstance::withoutGlobalScopes()->create([
            'tenant_id'          => $chatbot->tenant_id,
            'chatbot_id'         => $chatbot->id,
            'phone_number'       => $request->phone_number,
            'api_key'            => $request->api_key,
            'instance_id'        => $request->instance_id,
            'status'             => 'inactive',
            'typing_enabled'     => $request->boolean('typing_enabled'),
            'typing_duration_ms' => $request->input('typing_duration_ms', 2000),
        ]);

        return redirect()->route('admin.wa.index')->with('success', 'WA Instance berhasil ditambahkan.');
    }

    public function edit(WaInstance $waInstance)
    {
        $chatbots = Chatbot::all();
        $webhookUrl = $this->chatery->getWebhookUrl();
        return inertia('wa/Edit', [
            'waInstance' => $waInstance,
            'chatbots' => $chatbots,
            'webhookUrl' => $webhookUrl,
        ]);
    }

    public function update(Request $request, WaInstance $waInstance)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'api_key'      => 'nullable|string',
            'instance_id'  => 'nullable|string|max:100',
            'chatbot_id'         => 'required|exists:chatbots,id',
            'typing_enabled'     => 'nullable|boolean',
            'typing_duration_ms' => 'nullable|integer|min:500|max:10000',
        ]);

        $data = $request->only(['phone_number', 'instance_id', 'chatbot_id']);
        $data['typing_enabled']     = $request->boolean('typing_enabled');
        $data['typing_duration_ms'] = $request->input('typing_duration_ms', $waInstance->typing_duration_ms ?? 2000);

        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        }

        $waInstance->update($data);

        return back()->with('success', 'WA Instance berhasil diperbarui.');
    }

    public function testConnection(WaInstance $waInstance)
    {
        $instanceId = $waInstance->instance_id ?: 'default';
        $result     = $this->chatery->testConnection($waInstance->api_key, $instanceId);

        if ($result['success']) {
            $waInstance->update([
                'status'   => 'active',
                'metadata' => array_merge($waInstance->metadata ?? [], [
                    'last_error'      => null,
                    'last_tested_at'  => now()->toIso8601String(),
                    'last_test_ok'    => true,
                    'chatery_status'  => $result['status'] ?? 'connected',
                    'chatery_phone'   => $result['phone'] ?? null,
                ]),
            ]);

            return back()->with('success', 'Koneksi berhasil! Status Chatery: ' . ($result['status'] ?? 'connected'));
        }

        $errorMessage = $result['error'] ?? 'Unknown error';
        $hint         = $this->buildInstanceIdHint($waInstance->api_key, $instanceId);

        $waInstance->update([
            'status'   => 'error',
            'metadata' => array_merge($waInstance->metadata ?? [], [
                'last_error'     => $errorMessage,
                'last_tested_at' => now()->toIso8601String(),
                'last_test_ok'   => false,
            ]),
        ]);

        $fullMessage = 'Koneksi gagal: ' . $errorMessage . $hint;

        return back()
            ->with('error', $fullMessage)
            ->withErrors(['connection' => $fullMessage]);
    }

    public function previewSessions(Request $request)
    {
        $request->validate(['api_key' => 'required|string']);

        $result = $this->chatery->listSessions($request->api_key);

        return response()->json($result);
    }

    private function buildInstanceIdHint(string $apiKey, string $usedInstanceId): string
    {
        $list = $this->chatery->listSessions($apiKey);

        if (! $list['success'] || empty($list['sessions'])) {
            return ' Pastikan Instance ID sama dengan sessionId di dashboard Chatery (API Tester → GET /sessions).';
        }

        $lines = collect($list['sessions'])->map(function ($s) {
            $phone = $s['phone'] ? " ({$s['phone']})" : '';
            $st    = $s['status'] ?? '-';

            return "{$s['id']}{$phone} [{$st}]";
        })->implode(', ');

        return " Instance ID yang dicoba: \"{$usedInstanceId}\". ID sesi di Chatery: {$lines}.";
    }

    public function destroy(WaInstance $waInstance)
    {
        $waInstance->delete();
        return back()->with('success', 'WA Instance berhasil dihapus.');
    }
}
