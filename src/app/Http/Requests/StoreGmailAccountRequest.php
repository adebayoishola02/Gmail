<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGmailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // You can add authorization logic here
    }

    public function rules(): array
    {
        return [
            'authorization_code' => ['required', 'string'],
            'email_address' => ['required', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'is_active' => ['boolean']
        ];
    }
}
