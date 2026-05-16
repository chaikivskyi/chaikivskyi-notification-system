<?php

namespace App\DTOs;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use Illuminate\Support\Carbon;

readonly class UserNotificationData
{
    public function __construct(
        public string $userId,
        public UserNotificationChannel $channel,
        public string $body,
        public ?string $subject = null,
        public ?UserNotificationPriority $priority = null,
        public ?Carbon $scheduledAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['user_id'],
            UserNotificationChannel::from($data['channel']),
            $data['body'],
            $data['subject'] ?? null,
            empty($data['priority']) ? null : UserNotificationPriority::tryFrom($data['priority']),
            empty($data['scheduled_at']) ? null : Carbon::parse($data['scheduled_at']),
        );
    }
}
