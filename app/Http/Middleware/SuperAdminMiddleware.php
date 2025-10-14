<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

public function handle(Request $request, Closure $next)
{
    if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
        abort(403, 'This action requires Super Admin access.');
    }
    
    return $next($request);
}