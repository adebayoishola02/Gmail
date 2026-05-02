<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GmailAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'company_uuid'      => $this->company_uuid,
            'created_by_uuid'   => $this->created_by_uuid,
            'is_active'         => $this->is_active,
            'email_address'     => $this->email_address,
            'name'              => $this->name,
            'client_id'         => $this->client_id,
            'client_secret'     => $this->client_secret,
            'access_token'      => $this->access_token,
            'refresh_token'     => $this->refresh_token,
            'last_sync_at'      => $this->last_sync_at?->toIso8601String(),
            'token_expires_at'  => $this->token_expires_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
