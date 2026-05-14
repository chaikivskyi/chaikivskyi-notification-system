<?php

namespace App\Events;

use App\Models\UserNotification;
use Illuminate\Foundation\Events\Dispatchable;

class UserNotificationCreated
{
    use Dispatchable;

    public function __construct(public UserNotification $notification) {}
}
