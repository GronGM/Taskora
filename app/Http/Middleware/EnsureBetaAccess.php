<?php

namespace App\Http\Middleware;

use App\Support\BetaAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBetaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! BetaAccess::enabled() || $this->shouldPassThrough($request) || BetaAccess::hasAccess($request)) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('beta-access.show');
    }

    private function shouldPassThrough(Request $request): bool
    {
        if ($request->routeIs('beta-access.*') || $request->is('robots.txt') || $request->is('up')) {
            return true;
        }

        if ($request->is('build/*') || $request->is('storage/*')) {
            return true;
        }

        $path = $request->path();

        return $path === 'favicon.ico'
            || str_ends_with($path, '.css')
            || str_ends_with($path, '.js')
            || str_ends_with($path, '.map')
            || str_ends_with($path, '.woff')
            || str_ends_with($path, '.woff2')
            || str_ends_with($path, '.png')
            || str_ends_with($path, '.jpg')
            || str_ends_with($path, '.jpeg')
            || str_ends_with($path, '.webp')
            || str_ends_with($path, '.svg')
            || str_ends_with($path, '.ico');
    }
}
