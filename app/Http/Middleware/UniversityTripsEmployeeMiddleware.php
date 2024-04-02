<?php

namespace App\Http\Middleware;

use App\Enum\RulesEnum;
use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UniversityTripsEmployeeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role == RulesEnum::UNIVERSITY) {
            return $next($request);
        }
        return ResponseHelper::error('You are not authorized to do this action');
    }
}
