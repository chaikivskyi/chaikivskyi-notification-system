<?php

namespace App\Enums;

enum UserNotificationPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
}
