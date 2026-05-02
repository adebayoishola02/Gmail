<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGmailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'authorization_code' => ['required', 'string'],
            'email_address' => ['sometimes', 'email'],
            'name' => ['nullable', 'string', 'max:255'],
            'client_id' => ['sometimes', 'string'],
            'client_secret' => ['sometimes', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
