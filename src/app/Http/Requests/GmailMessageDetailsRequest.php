<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GmailMessageDetailsRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'account_uuid' => 'required|string',
            'message_id' => 'required|string',
        ];
    }
}
