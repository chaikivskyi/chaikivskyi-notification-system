<?php

namespace Database\Factories;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Enums\UserNotificationStatus;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'batch_id' => null,
            'channel' => fake()->randomElement(UserNotificationChannel::cases()),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'priority' => UserNotificationPriority::Normal,
            'status' => UserNotificationStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => UserNotificationStatus::Pending]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => ['status' => UserNotificationStatus::Delivered]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => UserNotificationStatus::Failed]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => ['status' => UserNotificationStatus::Canceled]);
    }

    public function forBatch(string $batchId): static
    {
        return $this->state(fn () => ['batch_id' => $batchId]);
    }

    public function forChannel(UserNotificationChannel $channel): static
    {
        return $this->state(fn () => ['channel' => $channel]);
    }
}
