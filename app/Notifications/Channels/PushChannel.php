<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toPush')) {
            throw new \RuntimeException(sprintf('%s must define toPush() method.', get_class($notification)));
        }

        $payload = $notification->toPush($notifiable);

        Log::info('Sending push notification', [
            'recipient' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            'payload' => $payload,
        ]);
    }
}
