<?php

namespace App\Http\Controllers;

use App\Services\BrandSettingsService;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(BrandSettingsService $brandService): Response
    {
        return Inertia::render('Landing', [
            'contactWhatsApp' => config('marketing.whatsapp_url'),
            'appUrl' => config('app.url'),
            'brand' => $brandService->getBrandColors(),
        ]);
    }
}
