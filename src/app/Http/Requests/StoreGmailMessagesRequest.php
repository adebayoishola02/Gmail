<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGmailMessagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'account_uuid' => 'required|string',
            'recipient' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ];
    }
}
