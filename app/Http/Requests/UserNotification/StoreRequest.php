<?php

namespace App\Http\Requests\UserNotification;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Rules\UserNotification\BodyByChannel;
use App\Rules\UserNotification\SubjectByChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'channel' => ['required', Rule::enum(UserNotificationChannel::class)],
            'body' => ['required', 'string', new BodyByChannel],
            'subject' => ['nullable', 'string', new SubjectByChannel],
            'priority' => ['nullable', Rule::enum(UserNotificationPriority::class)],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
