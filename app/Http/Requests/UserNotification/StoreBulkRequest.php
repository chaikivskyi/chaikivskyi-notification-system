<?php

namespace App\Http\Requests\UserNotification;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array', 'max:1000'],
            'notifications.*.user_id' => ['required', 'exists:users,id'],
            'notifications.*.channel' => ['required', Rule::enum(UserNotificationChannel::class)],
            'notifications.*.body' => ['required', 'string', 'max:10000'],
            'notifications.*.subject' => ['nullable', 'string', 'max:255'],
            'notifications.*.priority' => ['nullable', Rule::enum(UserNotificationPriority::class)],
        ];
    }
}
