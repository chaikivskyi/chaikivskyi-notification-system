<?php

namespace App\Rules\UserNotification;

use App\Enums\UserNotificationChannel;

class ChannelLookup
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function for(string $attribute, array $data): ?UserNotificationChannel
    {
        $segments = explode('.', $attribute);
        $segments[count($segments) - 1] = 'channel';
        $raw = data_get($data, implode('.', $segments));

        return is_string($raw) ? UserNotificationChannel::tryFrom($raw) : null;
    }
}
