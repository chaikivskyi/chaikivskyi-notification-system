<?php

namespace App\DTOs;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;

readonly class UserNotificationData
{
    public function __construct(
        public int $userId,
        public UserNotificationChannel $channel,
        public string $body,
        public ?string $subject = null,
        public ?UserNotificationPriority $priority = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['user_id'],
            UserNotificationChannel::from($data['channel']),
            $data['body'],
            $data['subject'] ?? null,
            empty($data['priority']) ? null : UserNotificationPriority::tryFrom($data['priority']),
        );
    }
}
