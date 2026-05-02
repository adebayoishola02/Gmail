<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class GmailMessagesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // data_get() checks for both $this->key and $this['key'] automatically
        return [
            'uuid'              => data_get($this, 'uuid') ?? data_get($this, 'google_message_id'),
            'google_message_id' => data_get($this, 'google_message_id'),
            'company_uuid'      => data_get($this, 'company_uuid'),
            'created_by_uuid'   => data_get($this, 'created_by_uuid'),
            'recipient'         => data_get($this, 'recipient'),
            'snippet'           => data_get($this, 'snippet'),
            'body'              => data_get($this, 'body'),
            'thread_id'         => data_get($this, 'thread_id'),
            'created_at'        => data_get($this, 'created_at'),
        ];
    }
}
