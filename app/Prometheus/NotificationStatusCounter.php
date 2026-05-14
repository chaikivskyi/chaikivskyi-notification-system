<?php

namespace App\Prometheus;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationStatus;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;

class NotificationStatusCounter
{
    public const NAMESPACE = '';

    public const NAME = 'notification_status_total';

    /**
     * @var list<string>
     */
    public const LABELS = ['channel', 'status'];

    public function __construct(private readonly CollectorRegistry $registry) {}

    public function inc(UserNotificationChannel $channel, UserNotificationStatus $status): void
    {
        $this->counter()->inc([$channel->value, $status->value]);
    }

    private function counter(): Counter
    {
        return $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            self::NAME,
            'Notifications by channel and the status they transitioned to',
            self::LABELS,
        );
    }
}
