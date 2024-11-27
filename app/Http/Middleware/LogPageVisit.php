<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;

class LogPageVisit
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Get the current route name or path
        $routeName = $request->route() ? $request->route()->getName() : $request->path();

        ActivityLogger::log(
            $routeName,
            'page_visit',
            'User visited ' . $routeName
        );

        return $response;
    }
}
