<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            throw new \RuntimeException(sprintf('%s must define toSms() method.', get_class($notification)));
        }

        $body = $notification->toSms($notifiable);

        Log::info('Sending SMS notification', [
            'recipient' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            'body' => $body,
        ]);
    }
}
