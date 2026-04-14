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
        $user   = Auth::user();
        $tenant = $user->isSuperAdmin()
            ? Tenant::find(request('tenant_id') ?? $user->tenant_id)
            : $user->tenant;

        $tenants = $user->isSuperAdmin() ? Tenant::orderBy('name')->get() : collect();

        // Pengaturan efektif: tenant override + global default
        $global = [
            'ai_api_key'     => config('services.sumopod.api_key', ''),
            'ai_base_url'    => config('services.sumopod.base_url', ''),
            'ai_embed_model' => config('services.sumopod.embed_model', ''),
            'ai_chat_model'  => config('services.sumopod.chat_model', ''),
        ];

        $tenantSettings = $tenant?->settings ?? [];

        return view('admin.settings.index', compact('global', 'tenantSettings', 'tenant', 'tenants'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'ai_api_key'     => 'nullable|string',
            'ai_base_url'    => 'nullable|url',
            'ai_embed_model' => 'nullable|string|max:100',
            'ai_chat_model'  => 'nullable|string|max:100',
        ]);

        $user = Auth::user();

        if ($user->isSuperAdmin() && $request->filled('tenant_id')) {
            $tenant = Tenant::findOrFail($request->tenant_id);
        } else {
            $tenant = $user->tenant;
        }

        if (! $tenant) {
            return back()->withErrors(['tenant' => 'Tenant tidak ditemukan.']);
        }

        $tenant->updateAiSettings($request->only([
            'ai_api_key', 'ai_base_url', 'ai_embed_model', 'ai_chat_model',
        ]));

        return $this->redirectToSettingsIndex($tenant)
            ->with('success', "Pengaturan AI untuk tenant \"{$tenant->name}\" berhasil disimpan!");
    }

    public function updateGlobal(Request $request)
    {
        $request->validate([
            'sumopod_api_key'     => 'nullable|string',
            'sumopod_base_url'    => 'required|url',
            'sumopod_embed_model' => 'required|string|max:100',
            'sumopod_chat_model'  => 'required|string|max:100',
        ]);

        $updates = [
            'SUMOPOD_BASE_URL'    => $request->sumopod_base_url,
            'SUMOPOD_EMBED_MODEL' => $request->sumopod_embed_model,
            'SUMOPOD_CHAT_MODEL'  => $request->sumopod_chat_model,
        ];

        // Jangan timpa API key jika field dikosongkan (password sering tidak dikirim / kosong saat tidak diubah)
        if ($request->filled('sumopod_api_key')) {
            $updates['SUMOPOD_API_KEY'] = $request->sumopod_api_key;
        }

        $this->mergeEnvValues(base_path('.env'), $updates);

        Artisan::call('config:clear');

        $tenant = null;
        if (Auth::user()->isSuperAdmin() && $request->filled('context_tenant_id')) {
            $tenant = Tenant::find($request->context_tenant_id);
        }

        return $this->redirectToSettingsIndex($tenant)
            ->with('success', 'Default global AI berhasil diperbarui!');
    }

    /**
     * Gabungkan nilai ke .env baris-per-baris agar aman untuk karakter $ dan lainnya (preg_replace sebelumnya bisa merusak).
     *
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
                if (preg_match('/^' . preg_quote($key, '/') . '=', $line) === 1) {
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

        if (preg_match('/^[\w\-.\/@:]+$/', $value) === 1) {
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
        try {
            $user   = Auth::user();
            $tenant = $user->isSuperAdmin() && $request->filled('tenant_id')
                ? Tenant::find($request->tenant_id)
                : $user->tenant;

            $service = app(SumopodService::class)
                ->withTenantSettings($tenant?->getAiConfig() ?? []);

            $ok = $service->testConnection();

            return response()->json([
                'success' => $ok,
                'message' => $ok ? 'Koneksi AI berhasil!' : 'Koneksi AI gagal.',
                'config'  => [
                    'base_url'    => $service->getConfig()['base_url'],
                    'chat_model'  => $service->getConfig()['chat_model'],
                    'embed_model' => $service->getConfig()['embed_model'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
