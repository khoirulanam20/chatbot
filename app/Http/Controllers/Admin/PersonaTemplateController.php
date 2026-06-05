<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersonaTemplateController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->tenant_id && ! $user->isSuperAdmin()) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:200',
            'tone' => 'nullable|string|in:ramah,formal,profesional,santai',
            'instructions' => 'nullable|string|max:5000',
            'restrictions' => 'nullable|string|max:5000',
            'greeting_style' => 'nullable|string|max:500',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $tenantId = $user->isSuperAdmin() && $request->filled('tenant_id')
            ? (int) $request->tenant_id
            : $user->tenant_id;

        if (! $tenantId) {
            return back()->with('error', 'Pilih tenant untuk menyimpan template persona.');
        }

        PersonaTemplate::create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'role' => $request->input('role', ''),
            'tone' => $request->input('tone', 'ramah'),
            'instructions' => $request->input('instructions', ''),
            'restrictions' => $request->input('restrictions', ''),
            'greeting_style' => $request->input('greeting_style', ''),
        ]);

        return back()->with('success', 'Template personal berhasil disimpan!');
    }

    public function destroy(PersonaTemplate $personaTemplate)
    {
        if ($personaTemplate->user_id !== Auth::id()) {
            abort(403);
        }

        $personaTemplate->delete();

        return back()->with('success', 'Template personal berhasil dihapus.');
    }
}
