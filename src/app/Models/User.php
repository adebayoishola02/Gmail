<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements Authenticatable
{
    use \Illuminate\Auth\Authenticatable;

    protected $guarded = [];

    // Retrieve the user manually
    public static function getAuthenticatedUser()
    {
        return auth()->user();
    }
}
