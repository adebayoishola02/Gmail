<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class AuthenticateWithAuthServer
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        // Validate token and get user details from the auth server
        $response = Http::withToken($token)->get('https://connect.dispatchable.com/auth/api/v1/user/details');

        if ($response->failed()) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $userData = $response->json();
        $userData = $userData['data'];

        // Convert the response into a User model instance (but do NOT save it in the database)
        $user = new User($userData);
        $user->exists = true; // Treat as existing user but without saving it to the DB

        // Set the authenticated user in Laravel's Auth system
        Auth::setUser($user);

        return $next($request);
    }
}
