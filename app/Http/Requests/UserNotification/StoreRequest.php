<?php

namespace App\Http\Requests\UserNotification;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'channel' => ['required', 'array', Rule::enum(UserNotificationChannel::class)],
            'body' => ['required', 'string', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::enum(UserNotificationPriority::class)],
        ];
    }
}
