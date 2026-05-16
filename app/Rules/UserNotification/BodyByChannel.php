<?php

namespace App\Rules\UserNotification;

use App\Enums\UserNotificationChannel;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class BodyByChannel implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $max = match (ChannelLookup::for($attribute, $this->data)) {
            UserNotificationChannel::Sms => 1600,
            UserNotificationChannel::Push => 240,
            UserNotificationChannel::Email, null => 10000,
        };

        if (mb_strlen($value) > $max) {
            $fail('validation.max.string')->translate(['max' => $max]);
        }
    }
}
