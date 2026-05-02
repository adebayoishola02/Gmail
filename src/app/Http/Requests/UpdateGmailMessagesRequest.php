<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGmailMessagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'account_uuid' => 'required|string',
            'is_read' => 'boolean',
            'labels' => 'array'
        ];
    }
}
