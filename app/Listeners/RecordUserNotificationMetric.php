<?php

namespace App\Listeners;

use App\Events\UserNotificationStatusTransitioned;
use App\Jobs\PersistUserNotificationMetric;

class RecordUserNotificationMetric
{
    public function handle(UserNotificationStatusTransitioned $event): void
    {
        PersistUserNotificationMetric::dispatch(
            $event->notification->id,
            $event->notification->channel,
            $event->newStatus,
            now(),
        );
    }
}
