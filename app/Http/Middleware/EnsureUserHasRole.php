<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = auth('sanctum')->user();
        if (!$userRole) {
            return ResponseHelper::error('User not exist.');
        }
        if (!in_array($userRole->role, $roles)) {
            return ResponseHelper::error('You are not authorized to do this action');
        }
        return $next($request);
    }
}
