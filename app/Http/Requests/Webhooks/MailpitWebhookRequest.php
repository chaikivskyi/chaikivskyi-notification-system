<?php

namespace App\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class MailpitWebhookRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'Tags' => ['nullable', 'array'],
            'Tags.*' => ['string'],
        ];
    }
}
