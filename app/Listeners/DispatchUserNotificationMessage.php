<?php

namespace App\Listeners;

use App\Events\UserNotificationCreated;
use App\Notifications\UserNotificationMessage;

class DispatchUserNotificationMessage
{
    public function handle(UserNotificationCreated $event): void
    {
        if (! $event->notification->is_scheduled) {
            UserNotificationMessage::dispatchFor($event->notification);
        }
    }
}
