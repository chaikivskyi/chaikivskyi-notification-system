<?php

namespace Tests\Feature\Api\UserNotification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

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
            'user_id' => 999999,
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
}
