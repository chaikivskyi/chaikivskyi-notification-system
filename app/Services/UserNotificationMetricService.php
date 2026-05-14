<?php

namespace App\Services;

use App\Enums\UserNotificationChannel;
use App\Models\UserNotificationMetric;
use App\Prometheus\DeliveryLatencyHistogram;
use App\Prometheus\MissedDeliveryLatencyCounter;
use Carbon\CarbonInterface;

class UserNotificationMetricService
{
    public function __construct(
        private readonly DeliveryLatencyHistogram $deliveryLatencyHistogram,
        private readonly MissedDeliveryLatencyCounter $missedDeliveryLatencyCounter,
    ) {}

    public function markFirstTransition(int $notificationId, string $column, CarbonInterface $occurredAt): bool
    {
        $inserted = UserNotificationMetric::query()->insertOrIgnore([
            'user_notification_id' => $notificationId,
            $column => $occurredAt,
        ]);

        if ($inserted === 1) {
            return true;
        }

        return UserNotificationMetric::query()
            ->whereKey($notificationId)
            ->whereNull($column)
            ->update([$column => $occurredAt]) === 1;
    }

    public function observeDeliveryLatency(
        int $notificationId,
        UserNotificationChannel $channel,
        CarbonInterface $occurredAt,
    ): void {
        $queuedAt = UserNotificationMetric::query()
            ->whereKey($notificationId)
            ->value('queued_at');

        if ($queuedAt === null) {
            $this->missedDeliveryLatencyCounter->inc($channel);

            return;
        }

        $latency = (float) max(
            0,
            $occurredAt->getTimestamp() - $queuedAt->getTimestamp(),
        );

        $this->deliveryLatencyHistogram->observe($latency);
    }
}
