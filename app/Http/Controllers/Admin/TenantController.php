<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount(['users', 'chatbots'])->paginate(20);
        return inertia('tenants/Index', ['tenants' => $tenants]);
    }

    public function create()
    {
        return inertia('tenants/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:50|unique:tenants|alpha_dash',
            'plan' => 'required|in:free,pro,enterprise',
        ]);

        Tenant::create([
            'name'      => $request->name,
            'slug'      => $request->slug,
            'plan'      => $request->plan,
            'is_active' => true,
        ]);

        return redirect()->route('admin.tenants.index')->with('success', 'Tenant berhasil dibuat!');
    }

    public function edit(Tenant $tenant)
    {
        $tenant->loadCount(['users', 'chatbots']);
        return inertia('tenants/Edit', ['tenant' => $tenant]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'plan'      => 'required|in:free,pro,enterprise',
            'is_active' => 'boolean',
        ]);

        $tenant->update([
            'name' => $request->name,
            'plan' => $request->plan,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tenant berhasil diperbarui!');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return back()->with('success', 'Tenant berhasil dihapus.');
    }
}
