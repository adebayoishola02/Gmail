<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || $user->uuid !== '6e6d7223-5bb6-4913-98e1-78a3692c1acb') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
