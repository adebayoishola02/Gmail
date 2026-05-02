<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class GmailMessages extends Model
{
    /** @use HasFactory<\Database\Factories\GmailMessagesFactory> */
    use HasFactory;

    protected $fillable = [
        'company_uuid',
        'created_by_uuid',
        'google_message_id',
        'thread_id',
        'recipient',
        'sender',
        'subject',
        'body',
        'type',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
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
