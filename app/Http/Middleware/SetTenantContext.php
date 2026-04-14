<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        if ($request->hasHeader('X-Tenant-ID')) {
            $tenantId = $request->header('X-Tenant-ID');
        } elseif ($host = $request->getHost()) {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomain = $parts[0];
                $tenant = Tenant::where('slug', $subdomain)->first();
                if ($tenant) {
                    $tenantId = $tenant->id;
                }
            }
        }

        if ($tenantId) {
            app()->instance('current_tenant_id', $tenantId);
            $request->attributes->set('tenant_id', $tenantId);
        }

        return $next($request);
    }
}
