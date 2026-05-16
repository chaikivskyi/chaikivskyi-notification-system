<?php

namespace App\Http\Requests\UserNotification;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Rules\UserNotification\BodyByChannel;
use App\Rules\UserNotification\SubjectByChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBulkRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array', 'max:1000'],
            'notifications.*.user_id' => ['required', 'uuid', 'exists:users,id'],
            'notifications.*.channel' => ['required', Rule::enum(UserNotificationChannel::class)],
            'notifications.*.body' => ['required', 'string', new BodyByChannel],
            'notifications.*.subject' => ['nullable', 'string', new SubjectByChannel],
            'notifications.*.priority' => ['nullable', Rule::enum(UserNotificationPriority::class)],
            'notifications.*.scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
