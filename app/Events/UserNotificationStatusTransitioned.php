<?php

namespace App\Events;

use App\Enums\UserNotificationStatus;
use App\Models\UserNotification;
use Illuminate\Foundation\Events\Dispatchable;

class UserNotificationStatusTransitioned
{
    use Dispatchable;

    public function __construct(
        public readonly UserNotification $notification,
        public readonly UserNotificationStatus $newStatus,
    ) {}
}
