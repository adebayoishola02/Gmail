<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use App\Models\User;


class CustomUserProvider implements UserProvider
{
    public function retrieveById($identifier) {}

    public function retrieveByToken($identifier, $token) {}

    public function updateRememberToken(Authenticatable $user, $token) {}

    public function retrieveByCredentials(array $credentials) {}

    public function validateCredentials(Authenticatable $user, array $credentials) { return false; }

    // Fix: Update method signature to match Laravel's UserProvider interface
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        return false;
    }

    public function retrieveByTokenFromRequest($request)
    {
        $userData = $request->attributes->get('user');
        if (!$userData) return null;

        $user = new User($userData);
        $user->exists = true;
        return $user;
    }
}

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider();
        });
    }
}
