<?php

namespace App\DTOs;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationStatus;
use Illuminate\Support\Carbon;

readonly class UserNotificationFilter
{
    public function __construct(
        public ?UserNotificationStatus $status = null,
        public ?UserNotificationChannel $channel = null,
        public ?Carbon $createdFrom = null,
        public ?Carbon $createdTo = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            empty($data['status']) ? null : UserNotificationStatus::tryFrom($data['status']),
            empty($data['channel']) ? null : UserNotificationChannel::tryFrom($data['channel']),
            empty($data['created_from']) ? null : Carbon::parse($data['created_from']),
            empty($data['created_to']) ? null : Carbon::parse($data['created_to']),
        );
    }
}
