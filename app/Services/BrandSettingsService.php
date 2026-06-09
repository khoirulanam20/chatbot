<?php

namespace App\Services;

use App\Models\SiteSetting;

class BrandSettingsService
{
    public function getBrandColors(): array
    {
        $defaults = config('marketing.brand', [
            'primary'         => '#0066FF',
            'primary_active'  => '#0052CC',
            'brand_accent'    => '#3399FF',
            'accent_muted'    => '#E6F0FF',
            'ink'             => '#1F2937',
        ]);

        $savedColors = SiteSetting::get('brand_colors', []);

        return array_merge($defaults, is_array($savedColors) ? $savedColors : []);
    }

    public function saveBrandColors(array $colors): void
    {
        SiteSetting::set('brand_colors', $colors);
    }
}
