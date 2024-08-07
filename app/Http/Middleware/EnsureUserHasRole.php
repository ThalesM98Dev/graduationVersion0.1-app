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
     * <<<<<<< HEAD
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param string ...$roles
     * =======
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param string ...$roles
     * >>>>>>> origin/main
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = auth('sanctum')->user();
        if (!$userRole) {
            return ResponseHelper::error(data: 'Invalid Token.');
        }
        if (!in_array($userRole->role, $roles)) {
            return ResponseHelper::error(data: 'You are not authorized to do this action.');
        }
        return $next($request);
    }
}
