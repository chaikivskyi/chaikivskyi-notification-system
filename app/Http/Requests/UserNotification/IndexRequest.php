<?php

namespace App\Http\Requests\UserNotification;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationStatus;
use App\Support\Pagination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'filters.channel' => ['nullable', Rule::enum(UserNotificationChannel::class)],
            'filters.status' => ['nullable', Rule::enum(UserNotificationStatus::class)],
            'filters.created_from' => ['nullable', 'date', 'before_or_equal:filters.created_to'],
            'filters.created_to' => ['nullable', 'date', 'after_or_equal:filters.created_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.Pagination::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
