<?php

namespace Database\Factories;

use App\Models\UserNotification;
use App\Models\UserNotificationMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotificationMetric>
 */
class UserNotificationMetricFactory extends Factory
{
    protected $model = UserNotificationMetric::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_notification_id' => UserNotification::factory(),
            'queued_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
        ];
    }

    public function delivered(int $latencySeconds = 2): static
    {
        return $this->state(function () use ($latencySeconds) {
            $queuedAt = now()->subSeconds($latencySeconds);

            return [
                'queued_at' => $queuedAt,
                'delivered_at' => $queuedAt->copy()->addSeconds($latencySeconds),
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'queued_at' => now()->subSeconds(3),
            'failed_at' => now(),
        ]);
    }
}
