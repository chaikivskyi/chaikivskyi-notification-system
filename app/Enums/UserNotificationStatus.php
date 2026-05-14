<?php

namespace App\Enums;

enum UserNotificationStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Canceled = 'canceled';
}
