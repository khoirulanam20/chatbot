<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BrandSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketingCmsController extends Controller
{
    public function index(BrandSettingsService $brandService)
    {
        return Inertia::render('admin/MarketingCms', [
            'brandColors' => $brandService->getBrandColors(),
        ]);
    }

    public function update(Request $request, BrandSettingsService $brandService)
    {
        $validated = $request->validate([
            'primary' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'primary_active' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'brand_accent' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_muted' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'ink' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ]);

        $brandService->saveBrandColors($validated);

        return back()->with('success', 'Warna brand berhasil diperbarui.');
    }
}
