<?php

namespace App\Jobs;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationStatus;
use App\Prometheus\NotificationStatusCounter;
use App\Services\UserNotificationMetricService;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

#[Tries(3)]
#[Backoff(10)]
class PersistUserNotificationMetric implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly UserNotificationChannel $channel,
        public readonly UserNotificationStatus $newStatus,
        public readonly CarbonInterface $occurredAt,
    ) {}

    public function handle(
        UserNotificationMetricService $metrics,
        NotificationStatusCounter $statusCounter,
    ): void {
        $column = match ($this->newStatus) {
            UserNotificationStatus::Pending => 'queued_at',
            UserNotificationStatus::Delivered => 'delivered_at',
            UserNotificationStatus::Failed => 'failed_at',
            UserNotificationStatus::Canceled => 'canceled_at',
            default => null,
        };

        if ($column === null) {
            $this->fail(new RuntimeException(sprintf(
                'PersistUserNotificationMetric received unsupported status %s for notification %d.',
                $this->newStatus->value,
                $this->notificationId,
            )));

            return;
        }

        if (! $metrics->markFirstTransition($this->notificationId, $column, $this->occurredAt)) {
            return;
        }

        try {
            if ($this->newStatus === UserNotificationStatus::Delivered) {
                $metrics->observeDeliveryLatency($this->notificationId, $this->channel, $this->occurredAt);
            }

            $statusCounter->inc($this->channel, $this->newStatus);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
