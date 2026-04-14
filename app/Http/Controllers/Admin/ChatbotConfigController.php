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
        $chatbots = Chatbot::with('embedConfig')->paginate(10);
        return view('admin.chatbot.index', compact('chatbots'));
    }

    public function create()
    {
        $tenants = Auth::user()->isSuperAdmin() ? Tenant::all() : collect([Auth::user()->tenant]);
        return view('admin.chatbot.create', compact('tenants'));
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
        return view('admin.chatbot.edit', compact('chatbot', 'tenants'));
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
            'avatar'           => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'system_prompt', 'model', 'temperature', 'max_context', 'language', 'fallback_message']);
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
        return view('admin.chatbot.embed-code', compact('chatbot'));
    }
}
