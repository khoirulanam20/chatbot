<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotEmbedConfig;
use App\Models\Chatbot;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatbotConfigController extends Controller
{
    public function index()
    {
        $chatbots = Chatbot::with('embedConfig')->withCount('conversations')->paginate(10);
        return inertia('chatbot/Index', ['chatbots' => $chatbots]);
    }

    public function create()
    {
        $tenants = Auth::user()->isSuperAdmin() ? Tenant::all() : collect([Auth::user()->tenant]);
        return inertia('chatbot/Create', ['tenants' => $tenants]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tenant_id'       => 'required|exists:tenants,id',
            'name'            => 'required|string|max:100',
            'system_prompt'   => 'nullable|string',
            'model'           => 'required|string',
            'temperature'     => 'required|numeric|min:0|max:1',
            'max_context'     => 'required|integer|min:1|max:50',
            'language'        => 'required|string|max:10',
            'fallback_message' => 'nullable|string',
            'handoff_triggers' => 'nullable|string',
            'avatar'          => 'nullable|image|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $chatbot = Chatbot::withoutGlobalScopes()->create([
            'tenant_id'        => $request->tenant_id,
            'name'             => $request->name,
            'system_prompt'    => $request->system_prompt,
            'model'            => $request->model,
            'temperature'      => $request->temperature,
            'max_context'      => $request->max_context,
            'language'         => $request->language,
            'fallback_message' => $request->fallback_message,
            'handoff_triggers' => $request->handoff_triggers
                ? array_map('trim', explode("\n", $request->handoff_triggers))
                : [],
            'avatar'    => $avatarPath,
            'is_active' => $request->boolean('is_active', true),
            'settings'  => [
                'agent_session_minutes' => 30,
                'agent_session_message' => \App\Services\AgentSessionService::DEFAULT_HOLD_MESSAGE,
            ],
        ]);

        BotEmbedConfig::create([
            'chatbot_id'    => $chatbot->id,
            'primary_color' => '#4F46E5',
            'position'      => 'bottom-right',
            'greeting'      => 'Halo! Ada yang bisa saya bantu?',
        ]);

        return redirect()->route('admin.chatbot.edit', $chatbot)->with('success', 'Chatbot berhasil dibuat!');
    }

    public function edit(Chatbot $chatbot)
    {
        $chatbot->load('embedConfig');
        $tenants = Auth::user()->isSuperAdmin() ? Tenant::all() : collect([Auth::user()->tenant]);
        return inertia('chatbot/Edit', ['chatbot' => $chatbot, 'tenants' => $tenants]);
    }

    public function update(Request $request, Chatbot $chatbot)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'system_prompt'    => 'nullable|string',
            'model'            => 'required|string',
            'temperature'      => 'required|numeric|min:0|max:1',
            'max_context'      => 'required|integer|min:1|max:50',
            'language'         => 'required|string|max:10',
            'fallback_message' => 'nullable|string',
            'handoff_triggers' => 'nullable|string',
            'avatar'                 => 'nullable|image|max:2048',
            'agent_session_minutes'  => 'nullable|integer|min:1|max:1440',
            'agent_session_message'  => 'nullable|string|max:500',
        ]);

        $data = $request->only(['name', 'system_prompt', 'model', 'temperature', 'max_context', 'language', 'fallback_message']);
        $data['settings'] = array_merge($chatbot->settings ?? [], [
            'agent_session_minutes' => (int) ($request->agent_session_minutes ?? 30),
            'agent_session_message' => $request->agent_session_message
                ?: \App\Services\AgentSessionService::DEFAULT_HOLD_MESSAGE,
        ]);
        $data['is_active']        = $request->boolean('is_active');
        $data['handoff_triggers'] = $request->handoff_triggers
            ? array_map('trim', explode("\n", $request->handoff_triggers))
            : [];

        if ($request->hasFile('avatar')) {
            if ($chatbot->avatar) {
                Storage::disk('public')->delete($chatbot->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $chatbot->update($data);

        $embedData = $request->only(['primary_color', 'position', 'size', 'greeting', 'sound_enabled', 'auto_open_delay']);
        $embedData['sound_enabled']    = $request->boolean('sound_enabled');
        $embedData['allow_file_upload'] = $request->boolean('allow_file_upload');
        $embedData['quick_replies']    = $request->quick_replies
            ? array_filter(array_map('trim', explode("\n", $request->quick_replies)))
            : [];

        $chatbot->embedConfig()->updateOrCreate(
            ['chatbot_id' => $chatbot->id],
            $embedData
        );

        return back()->with('success', 'Konfigurasi chatbot berhasil disimpan!');
    }

    public function destroy(Chatbot $chatbot)
    {
        if ($chatbot->avatar) {
            Storage::disk('public')->delete($chatbot->avatar);
        }
        $chatbot->delete();

        return redirect()->route('admin.chatbot.index')->with('success', 'Chatbot berhasil dihapus.');
    }

    public function embedCode(Chatbot $chatbot)
    {
        return inertia('chatbot/EmbedCode', ['chatbot' => $chatbot]);
    }
}
