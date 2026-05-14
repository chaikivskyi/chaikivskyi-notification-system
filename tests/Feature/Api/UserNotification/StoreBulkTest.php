<?php

namespace Tests\Feature\Api\UserNotification;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreBulkTest extends TestCase
{
    use RefreshDatabase;

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
                ['user_id' => 999999, 'channel' => 'sms', 'body' => 'b'],
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
}
