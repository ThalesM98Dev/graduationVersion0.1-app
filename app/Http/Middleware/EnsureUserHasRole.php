<?php

namespace App\Http\Middleware;

use App\Enum\RolesEnum;
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
        $user = auth('sanctum')->user();
        if (!$user) {
            return ResponseHelper::error(data: 'Invalid Token.');
        }
        if ($user->role == RolesEnum::ADMIN->name) {//Only Admin
            return $next($request);
        }
        if (!in_array($user->role, $roles)) {
            return ResponseHelper::error(data: 'You are not authorized to do this action.');
        }
        return $next($request);
    }
}
