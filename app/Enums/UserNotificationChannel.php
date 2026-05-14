<?php

namespace App\Enums;

enum UserNotificationChannel: string
{
    case Sms = 'sms';
    case Email = 'email';
    case Push = 'push';
}
