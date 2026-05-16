<?php

namespace App\Rules\UserNotification;

use App\Enums\UserNotificationChannel;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class SubjectByChannel implements DataAwareRule, ValidationRule
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
        $channel = ChannelLookup::for($attribute, $this->data);

        if ($channel === UserNotificationChannel::Sms) {
            if ($value !== null && $value !== '') {
                $fail('validation.prohibited')->translate();
            }

            return;
        }

        if (! is_string($value)) {
            return;
        }

        $max = match ($channel) {
            UserNotificationChannel::Push => 65,
            UserNotificationChannel::Email, null => 255,
        };

        if (mb_strlen($value) > $max) {
            $fail('validation.max.string')->translate(['max' => $max]);
        }
    }
}
