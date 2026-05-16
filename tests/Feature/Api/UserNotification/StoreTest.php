<?php

namespace Tests\Feature\Api\UserNotification;

use App\Models\User;
use App\Notifications\UserNotificationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_creates_notification_with_required_fields(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'Hello',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'normal');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'Hello',
            'status' => 'pending',
        ]);

        Notification::assertSentTo($user, UserNotificationMessage::class);
    }

    public function test_honors_explicit_priority(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'body' => 'x',
            'priority' => 'high',
        ])
            ->assertCreated()
            ->assertJsonPath('data.priority', 'high');
    }

    public function test_persists_subject(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
            'subject' => 'Subj',
        ])
            ->assertCreated()
            ->assertJsonPath('data.subject', 'Subj');
    }

    public function test_returns_detail_resource_shape(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'user_id', 'batch_id', 'channel', 'status', 'priority',
                    'subject', 'body', 'created_at', 'updated_at',
                ],
            ]);
    }

    public function test_rejects_missing_required_fields(): void
    {
        $this->postJson('/api/user-notifications', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'channel', 'body']);
    }

    public function test_rejects_unknown_user_id(): void
    {
        $this->postJson('/api/user-notifications', [
            'user_id' => '01900000-0000-7000-8000-000000000000',
            'channel' => 'email',
            'body' => 'x',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    public function test_rejects_invalid_channel(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'fax',
            'body' => 'x',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('channel');
    }

    public function test_rejects_invalid_priority(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
            'priority' => 'super',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('priority');
    }

    public function test_rejects_too_long_body(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => str_repeat('a', 10001),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_rejects_too_long_subject(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
            'subject' => str_repeat('a', 256),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_rejects_too_long_sms_body(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'body' => str_repeat('a', 1601),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_accepts_sms_body_at_limit(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'body' => str_repeat('a', 1600),
        ])->assertCreated();
    }

    public function test_rejects_subject_for_sms(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'body' => 'x',
            'subject' => 'Subj',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_rejects_too_long_push_body(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'push',
            'body' => str_repeat('a', 241),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_accepts_push_body_at_limit(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'push',
            'body' => str_repeat('a', 240),
        ])->assertCreated();
    }

    public function test_rejects_too_long_push_subject(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'push',
            'body' => 'x',
            'subject' => str_repeat('a', 66),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_accepts_push_subject_at_limit(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'push',
            'body' => 'x',
            'subject' => str_repeat('a', 65),
        ])->assertCreated();
    }

    public function test_stores_schedule_when_scheduled_at_is_present(): void
    {
        $user = User::factory()->create();
        $scheduledAt = now()->addHour()->startOfSecond();

        $response = $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ])->assertCreated();

        $notificationId = $response->json('data.id');

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notificationId,
            'is_scheduled' => true,
        ]);
        $this->assertDatabaseHas('user_notification_schedules', [
            'user_notification_id' => $notificationId,
            'scheduled_at' => $scheduledAt->toDateTimeString(),
        ]);
    }

    public function test_does_not_store_schedule_when_scheduled_at_is_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
        ])->assertCreated();

        $notificationId = $response->json('data.id');

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notificationId,
            'is_scheduled' => false,
        ]);
        $this->assertDatabaseMissing('user_notification_schedules', [
            'user_notification_id' => $notificationId,
        ]);
    }

    public function test_rejects_past_scheduled_at(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
            'scheduled_at' => now()->subHour()->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_at');
    }

    public function test_rejects_invalid_scheduled_at(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/user-notifications', [
            'user_id' => $user->id,
            'channel' => 'email',
            'body' => 'x',
            'scheduled_at' => 'not-a-date',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_at');
    }
}
