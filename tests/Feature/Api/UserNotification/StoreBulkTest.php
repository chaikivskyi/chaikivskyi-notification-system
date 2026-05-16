<?php

namespace Tests\Feature\Api\UserNotification;

use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\UserNotificationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreBulkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_inserts_all_items_with_shared_batch_id(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => 'b'],
                ['user_id' => $user->id, 'channel' => 'push', 'body' => 'c'],
            ],
        ]);

        $response->assertOk()->assertJsonCount(3, 'data');

        $this->assertSame(3, UserNotification::count());
        $batchIds = UserNotification::query()->pluck('batch_id')->unique();
        $this->assertCount(1, $batchIds);
        $this->assertNotNull($batchIds->first());

        Notification::assertSentToTimes($user, UserNotificationMessage::class, 3);
    }

    public function test_sets_all_inserted_rows_to_pending(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => 'b'],
            ],
        ])->assertOk();

        $this->assertSame(2, UserNotification::where('status', 'pending')->count());
    }

    public function test_rejects_empty_notifications_array(): void
    {
        $this->postJson('/api/user-notifications/bulk', ['notifications' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications');
    }

    public function test_rejects_missing_notifications_key(): void
    {
        $this->postJson('/api/user-notifications/bulk', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications');
    }

    public function test_rejects_over_max_items(): void
    {
        $user = User::factory()->create();
        $items = array_fill(0, 1001, ['user_id' => $user->id, 'channel' => 'email', 'body' => 'x']);

        $this->postJson('/api/user-notifications/bulk', ['notifications' => $items])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications');
    }

    public function test_rejects_when_any_item_is_invalid(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                ['user_id' => '01900000-0000-7000-8000-000000000000', 'channel' => 'sms', 'body' => 'b'],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.1.user_id');
    }

    public function test_rejects_invalid_channel_in_item(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'fax', 'body' => 'a'],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.0.channel');
    }

    public function test_rejects_too_long_sms_body_in_item(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => str_repeat('a', 1601)],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.1.body');
    }

    public function test_rejects_too_long_push_body_in_item(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'push', 'body' => str_repeat('a', 241)],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.0.body');
    }

    public function test_rejects_subject_for_sms_in_item(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => 'x', 'subject' => 'Subj'],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.0.subject');
    }

    public function test_rejects_too_long_push_subject_in_item(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'push', 'body' => 'x', 'subject' => str_repeat('a', 66)],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.0.subject');
    }

    public function test_accepts_channel_specific_limits_in_items(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => str_repeat('a', 1600)],
                ['user_id' => $user->id, 'channel' => 'push', 'body' => str_repeat('a', 240), 'subject' => str_repeat('a', 65)],
                ['user_id' => $user->id, 'channel' => 'email', 'body' => str_repeat('a', 10000), 'subject' => str_repeat('a', 255)],
            ],
        ])->assertOk();
    }

    public function test_creates_schedules_only_for_items_with_scheduled_at(): void
    {
        $user = User::factory()->create();
        $scheduledAt = now()->addHour()->startOfSecond();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => 'b', 'scheduled_at' => $scheduledAt->toIso8601String()],
            ],
        ])->assertOk();

        $this->assertSame(1, UserNotification::where('is_scheduled', true)->count());
        $this->assertSame(1, UserNotification::where('is_scheduled', false)->count());

        $scheduledNotification = UserNotification::where('is_scheduled', true)->firstOrFail();
        $this->assertSame('sms', $scheduledNotification->channel->value);
        $this->assertDatabaseHas('user_notification_schedules', [
            'user_notification_id' => $scheduledNotification->id,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
        ]);
        $this->assertDatabaseCount('user_notification_schedules', 1);
    }

    public function test_rejects_past_scheduled_at_in_item(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications/bulk', [
            'notifications' => [
                ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                ['user_id' => $user->id, 'channel' => 'sms', 'body' => 'b', 'scheduled_at' => now()->subHour()->toIso8601String()],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notifications.1.scheduled_at');
    }
}
