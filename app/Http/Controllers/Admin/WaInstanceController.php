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
        return view('admin.wa.index', compact('instances'));
    }

    public function create()
    {
        $chatbots = Chatbot::all();
        return view('admin.wa.create', compact('chatbots'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'chatbot_id'   => 'required|exists:chatbots,id',
            'phone_number' => 'required|string|max:20',
            'api_key'      => 'required|string',
            'instance_id'  => 'nullable|string|max:100',
        ]);

        $chatbot = Chatbot::findOrFail($request->chatbot_id);

        WaInstance::withoutGlobalScopes()->create([
            'tenant_id'    => $chatbot->tenant_id,
            'chatbot_id'   => $chatbot->id,
            'phone_number' => $request->phone_number,
            'api_key'      => $request->api_key,
            'instance_id'  => $request->instance_id,
            'status'       => 'inactive',
        ]);

        return redirect()->route('admin.wa.index')->with('success', 'WA Instance berhasil ditambahkan.');
    }

    public function edit(WaInstance $waInstance)
    {
        $chatbots = Chatbot::all();
        $webhookUrl = $this->chatery->getWebhookUrl();
        return view('admin.wa.edit', compact('waInstance', 'chatbots', 'webhookUrl'));
    }

    public function update(Request $request, WaInstance $waInstance)
    {
        $request->validate([
            'phone_number' => 'required|string|max:20',
            'api_key'      => 'nullable|string',
            'instance_id'  => 'nullable|string|max:100',
            'chatbot_id'   => 'required|exists:chatbots,id',
        ]);

        $data = $request->only(['phone_number', 'instance_id', 'chatbot_id']);

        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        }

        $waInstance->update($data);

        return back()->with('success', 'WA Instance berhasil diperbarui.');
    }

    public function testConnection(WaInstance $waInstance)
    {
        $result = $this->chatery->testConnection($waInstance->api_key);

        if ($result['success']) {
            $waInstance->update(['status' => 'active']);
            return back()->with('success', 'Koneksi berhasil! Status: ' . ($result['status'] ?? 'connected'));
        }

        $waInstance->update(['status' => 'error']);
        return back()->withErrors(['connection' => 'Koneksi gagal: ' . ($result['error'] ?? 'Unknown error')]);
    }

    public function destroy(WaInstance $waInstance)
    {
        $waInstance->delete();
        return back()->with('success', 'WA Instance berhasil dihapus.');
    }
}
