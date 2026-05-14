<?php

namespace App\Listeners;

use App\Events\UserNotificationCreated;
use App\Notifications\UserNotificationMessage;

class DispatchUserNotificationMessage
{
    public function handle(UserNotificationCreated $event): void
    {
        UserNotificationMessage::dispatchFor($event->notification);
    }
}
