<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class GmailAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_uuid',
        'created_by_uuid',
        'email_address',
        'name',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'client_id'   => 'encrypted',
        'client_secret'   => 'encrypted',
        'access_token'   => 'encrypted',
        'refresh_token'   => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
