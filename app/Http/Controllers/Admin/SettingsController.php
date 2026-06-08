<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\SumopodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $tenant = Tenant::find(request('tenant_id'))
                ?? ($user->tenant_id ? Tenant::find($user->tenant_id) : null)
                ?? Tenant::orderBy('name')->first();
        } else {
            $tenant = $user->tenant;
        }

        $tenants = $user->isSuperAdmin() ? Tenant::orderBy('name')->get() : collect();

        $global = [
            'has_ai_api_key'   => filled(config('services.sumopod.api_key')),
            'ai_base_url'      => config('services.sumopod.base_url', ''),
            'ai_embed_model'   => config('services.sumopod.embed_model', ''),
            'ai_chat_model'    => config('services.sumopod.chat_model', ''),
            'ai_vision_model'  => config('services.sumopod.vision_model', ''),
        ];

        $tenantSettings = [
            'has_ai_api_key'  => $tenant?->hasAiApiKey() ?? false,
            'ai_base_url'     => $tenant?->settings[Tenant::AI_BASE_URL] ?? '',
            'ai_embed_model'  => $tenant?->settings[Tenant::AI_EMBED_MODEL] ?? '',
            'ai_chat_model'   => $tenant?->settings[Tenant::AI_CHAT_MODEL] ?? '',
            'ai_vision_model' => $tenant?->settings[Tenant::AI_VISION_MODEL] ?? '',
        ];

        return inertia('settings/Index', [
            'global' => $global,
            'tenantSettings' => $tenantSettings,
            'tenant' => $tenant,
            'tenants' => $tenants,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'tenant_id'      => 'nullable|integer|exists:tenants,id',
            'ai_api_key'     => 'nullable|string',
            'ai_base_url'    => 'nullable|url',
            'ai_embed_model'  => 'nullable|string|max:100',
            'ai_chat_model'   => 'nullable|string|max:100',
            'ai_vision_model' => 'nullable|string|max:100',
        ]);

        if ($request->filled('ai_embed_model') && ($embedError = SumopodService::validateEmbedModelName($request->ai_embed_model))) {
            return back()->withErrors(['ai_embed_model' => $embedError]);
        }

        $user = Auth::user();

        if ($user->isSuperAdmin() && $request->filled('tenant_id')) {
            $tenant = Tenant::findOrFail($request->tenant_id);
        } else {
            $tenant = $user->tenant;
        }

        if (! $tenant) {
            return back()->withErrors(['tenant' => 'Tenant tidak ditemukan.']);
        }

        $tenant->updateAiSettings($request->only(Tenant::AI_FIELDS));

        return $this->redirectToSettingsIndex($tenant)
            ->with('success', "Pengaturan AI untuk tenant \"{$tenant->name}\" berhasil disimpan!");
    }

    public function updateGlobal(Request $request)
    {
        $request->validate([
            'sumopod_api_key'     => 'nullable|string',
            'sumopod_base_url'    => 'required|url',
            'sumopod_embed_model'  => 'required|string|max:100',
            'sumopod_chat_model'   => 'required|string|max:100',
            'sumopod_vision_model' => 'nullable|string|max:100',
        ]);

        if ($embedError = SumopodService::validateEmbedModelName($request->sumopod_embed_model)) {
            return back()->withErrors(['sumopod_embed_model' => $embedError]);
        }

        $updates = [
            'SUMOPOD_BASE_URL'     => $request->sumopod_base_url,
            'SUMOPOD_EMBED_MODEL'  => $request->sumopod_embed_model,
            'SUMOPOD_CHAT_MODEL'   => $request->sumopod_chat_model,
            'SUMOPOD_VISION_MODEL' => $request->input('sumopod_vision_model', ''),
        ];

        if ($request->filled('sumopod_api_key')) {
            $updates['SUMOPOD_API_KEY'] = $request->sumopod_api_key;
        }

        try {
            $this->mergeEnvValues(base_path('.env'), $updates);
            Artisan::call('config:clear');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['global' => $e->getMessage()]);
        }

        $tenant = null;
        if (Auth::user()->isSuperAdmin() && $request->filled('context_tenant_id')) {
            $tenant = Tenant::find($request->context_tenant_id);
        }

        return $this->redirectToSettingsIndex($tenant)
            ->with('success', 'Default global AI berhasil diperbarui!');
    }

    /**
     * @param  array<string, string>  $updates
     */
    private function mergeEnvValues(string $path, array $updates): void
    {
        if (! is_readable($path) || ! is_writable($path)) {
            throw new \RuntimeException('File .env tidak dapat dibaca/ditulis.');
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Gagal membaca .env');
        }

        $eol = str_contains($raw, "\r\n") ? "\r\n" : "\n";
        $lines = preg_split("/\r\n|\n|\r/", $raw);
        $lines = $lines === false ? [] : $lines;

        $seen = array_fill_keys(array_keys($updates), false);

        foreach ($lines as $i => $line) {
            foreach ($updates as $key => $value) {
                if (preg_match('/^' . preg_quote($key, '/') . '=/', $line) === 1) {
                    $lines[$i] = $this->formatEnvLine($key, $value);
                    $seen[$key] = true;
                    break;
                }
            }
        }

        foreach ($updates as $key => $value) {
            if (! $seen[$key]) {
                $lines[] = $this->formatEnvLine($key, $value);
            }
        }

        $out = implode($eol, $lines);
        if ($out !== '' && ! str_ends_with($out, "\n")) {
            $out .= $eol;
        }

        file_put_contents($path, $out);
    }

    private function formatEnvLine(string $key, string $value): string
    {
        if ($value === '') {
            return "{$key}=";
        }

        if (preg_match('/^[\w\-.\/@:]+$/u', $value) === 1) {
            return "{$key}={$value}";
        }

        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

        return "{$key}=\"{$escaped}\"";
    }

    private function redirectToSettingsIndex(?Tenant $tenant): RedirectResponse
    {
        if (Auth::user()->isSuperAdmin() && $tenant) {
            return redirect()->route('admin.settings.index', ['tenant_id' => $tenant->id]);
        }

        return redirect()->route('admin.settings.index');
    }

    public function testAI(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'tenant_id'      => 'nullable|integer|exists:tenants,id',
            'ai_api_key'     => 'nullable|string',
            'ai_base_url'    => 'nullable|url',
            'ai_embed_model'  => 'nullable|string|max:100',
            'ai_chat_model'   => 'nullable|string|max:100',
            'ai_vision_model' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $tenant = $user->isSuperAdmin() && $request->filled('tenant_id')
            ? Tenant::findOrFail($request->tenant_id)
            : $user->tenant;

        $formOverrides = array_filter(
            $request->only(Tenant::AI_FIELDS),
            fn ($value) => $value !== null && $value !== ''
        );

        $mergedSettings = array_merge($tenant?->getAiConfig() ?? [], $formOverrides);

        $service = app(SumopodService::class)->withTenantSettings($mergedSettings);

        return response()->json($service->testConnection());
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Hanya admin yang dapat mengubah pengaturan AI.');
    }
}
