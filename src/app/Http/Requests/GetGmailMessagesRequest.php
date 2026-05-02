<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetGmailMessagesRequest extends FormRequest
{

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('label_id')) {
            $this->merge([
                'label_id' => strtoupper($this->label_id),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'account_uuid' => 'required|string',
            'label_id' => 'required|string',
            'next_page_token' => 'sometimes|string',
            'per_page' => 'nullable|numeric',
        ];
    }
}
