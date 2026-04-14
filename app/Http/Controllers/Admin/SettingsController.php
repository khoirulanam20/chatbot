<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\SumopodService;
use Illuminate\Http\Request;
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

        return back()->with('success', "Pengaturan AI untuk tenant \"{$tenant->name}\" berhasil disimpan!");
    }

    public function updateGlobal(Request $request)
    {
        $request->validate([
            'sumopod_api_key'     => 'nullable|string',
            'sumopod_base_url'    => 'required|url',
            'sumopod_embed_model' => 'required|string|max:100',
            'sumopod_chat_model'  => 'required|string|max:100',
        ]);

        $envPath    = base_path('.env');
        $envContent = file_get_contents($envPath);

        $updates = [
            'SUMOPOD_API_KEY'     => $request->sumopod_api_key ?? '',
            'SUMOPOD_BASE_URL'    => $request->sumopod_base_url,
            'SUMOPOD_EMBED_MODEL' => $request->sumopod_embed_model,
            'SUMOPOD_CHAT_MODEL'  => $request->sumopod_chat_model,
        ];

        foreach ($updates as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);

        return back()->with('success', 'Default global AI berhasil diperbarui!');
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
