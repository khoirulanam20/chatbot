<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotEmbedConfig;
use App\Models\Chatbot;
use App\Models\PersonaTemplate;
use App\Models\Tenant;
use App\Services\PersonaGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatbotConfigController extends Controller
{
    public function __construct(
        private PersonaGeneratorService $personaGenerator
    ) {}
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
                'takeover_keywords' => [],
                'takeover_idle_minutes' => 30,
                'takeover_hold_message' => \App\Services\AgentSessionService::DEFAULT_TAKEOVER_HOLD_MESSAGE,
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
            'temperature'      => 'required|numeric|min:0|max:1',
            'max_context'      => 'required|integer|min:1|max:50',
            'language'         => 'required|string|max:10',
            'fallback_message' => 'nullable|string',
            'handoff_triggers' => 'nullable|string',
            'avatar'                 => 'nullable|image|max:2048',
            'agent_session_minutes'  => 'nullable|integer|min:1|max:1440',
            'agent_session_message'  => 'nullable|string|max:500',
            'takeover_keywords'      => 'nullable|string',
            'takeover_idle_minutes'  => 'nullable|integer|min:1|max:1440',
            'takeover_hold_message'  => 'nullable|string|max:500',
        ]);

        $takeoverKeywords = $request->takeover_keywords
            ? array_values(array_filter(array_map('trim', explode("\n", $request->takeover_keywords))))
            : ($request->handoff_triggers
                ? array_values(array_filter(array_map('trim', explode("\n", $request->handoff_triggers))))
                : ($chatbot->settings['takeover_keywords'] ?? $chatbot->handoff_triggers ?? []));

        $data = $request->only(['name', 'temperature', 'max_context', 'language', 'fallback_message']);
        $data['settings'] = array_merge($chatbot->settings ?? [], [
            'agent_session_minutes' => (int) ($request->agent_session_minutes ?? 30),
            'agent_session_message' => $request->agent_session_message
                ?: \App\Services\AgentSessionService::DEFAULT_HOLD_MESSAGE,
            'takeover_keywords' => $takeoverKeywords,
            'takeover_idle_minutes' => (int) ($request->takeover_idle_minutes
                ?? $request->agent_session_minutes
                ?? 30),
            'takeover_hold_message' => $request->takeover_hold_message
                ?: \App\Services\AgentSessionService::DEFAULT_TAKEOVER_HOLD_MESSAGE,
        ]);
        $data['is_active']        = $request->boolean('is_active');
        $data['handoff_triggers'] = $takeoverKeywords;

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

    public function persona(Chatbot $chatbot)
    {
        $persona = array_merge([
            'role' => '',
            'tone' => 'ramah',
            'instructions' => '',
            'restrictions' => '',
            'greeting_style' => '',
            'humanize' => Chatbot::defaultHumanizeSettings(),
        ], $chatbot->getPersona());
        $persona['humanize'] = array_merge(
            Chatbot::defaultHumanizeSettings(),
            is_array($persona['humanize'] ?? null) ? $persona['humanize'] : []
        );

        $user = Auth::user();

        return inertia('chatbot/Persona', [
            'chatbot' => $chatbot->only(['id', 'name', 'tenant_id']),
            'persona' => $persona,
            'effective_system_prompt' => $chatbot->getEffectiveSystemPrompt(),
            'uses_legacy_prompt' => ! $chatbot->hasPersona() && filled($chatbot->system_prompt),
            'legacy_system_prompt' => $chatbot->system_prompt,
            'custom_templates' => $this->mapCustomTemplates($chatbot->tenant_id, $user),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapCustomTemplates(?int $tenantId, $user): array
    {
        return PersonaTemplate::where('user_id', $user->id)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonaTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'role' => $template->role,
                'tone' => $template->tone,
                'instructions' => $template->instructions,
                'restrictions' => $template->restrictions,
                'greeting_style' => $template->greeting_style,
                'can_delete' => true,
            ])
            ->values()
            ->all();
    }

    public function generatePersona(Request $request, Chatbot $chatbot)
    {
        $request->validate([
            'description' => 'required|string|max:500',
        ]);

        try {
            $persona = $this->personaGenerator->generate($chatbot, $request->description);

            return response()->json([
                'success' => true,
                'persona' => $persona,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat persona: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updatePersona(Request $request, Chatbot $chatbot)
    {
        $request->validate([
            'role' => 'nullable|string|max:200',
            'tone' => 'nullable|string|in:ramah,formal,profesional,santai',
            'instructions' => 'nullable|string|max:5000',
            'restrictions' => 'nullable|string|max:5000',
            'greeting_style' => 'nullable|string|max:500',
            'humanize.enabled' => 'nullable|boolean',
            'humanize.channels' => 'nullable|array',
            'humanize.channels.*' => 'in:whatsapp,web',
            'humanize.emoji_level' => 'nullable|in:none,minimal,medium,often',
            'humanize.message_length' => 'nullable|in:short,medium,long',
            'humanize.split_bubbles' => 'nullable|boolean',
            'humanize.pacing_ms' => 'nullable|integer|min:500|max:3000',
            'humanize.use_fillers' => 'nullable|boolean',
            'humanize.avoid_markdown' => 'nullable|boolean',
        ]);

        $channels = $request->input('humanize.channels', ['whatsapp', 'web']);
        if (! is_array($channels)) {
            $channels = ['whatsapp', 'web'];
        }
        $channels = array_values(array_filter($channels, fn ($c) => in_array($c, ['whatsapp', 'web'], true)));

        $settings = $chatbot->settings ?? [];
        $settings['persona'] = [
            'role' => $request->input('role', ''),
            'tone' => $request->input('tone', 'ramah'),
            'instructions' => $request->input('instructions', ''),
            'restrictions' => $request->input('restrictions', ''),
            'greeting_style' => $request->input('greeting_style', ''),
            'humanize' => [
                'enabled' => $request->boolean('humanize.enabled'),
                'channels' => $channels ?: ['whatsapp', 'web'],
                'emoji_level' => $request->input('humanize.emoji_level', 'minimal'),
                'message_length' => $request->input('humanize.message_length', 'short'),
                'split_bubbles' => $request->boolean('humanize.split_bubbles'),
                'pacing_ms' => (int) $request->input('humanize.pacing_ms', 1200),
                'use_fillers' => $request->boolean('humanize.use_fillers'),
                'avoid_markdown' => $request->boolean('humanize.avoid_markdown'),
            ],
        ];
        $chatbot->update(['settings' => $settings]);
        $chatbot->refresh();

        return back()->with('success', 'Persona berhasil disimpan!');
    }
}
