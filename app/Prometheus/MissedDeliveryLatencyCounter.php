<?php

namespace App\Prometheus;

use App\Enums\UserNotificationChannel;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;

class MissedDeliveryLatencyCounter
{
    public const NAMESPACE = '';

    public const NAME = 'notification_latency_missed_total';

    /**
     * @var list<string>
     */
    public const LABELS = ['channel'];

    public function __construct(private readonly CollectorRegistry $registry) {}

    public function inc(UserNotificationChannel $channel): void
    {
        $this->counter()->inc([$channel->value]);
    }

    private function counter(): Counter
    {
        return $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            self::NAME,
            'Delivered transitions where queued_at was unavailable, so latency could not be observed',
            self::LABELS,
        );
    }
}
