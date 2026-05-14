<?php

namespace Tests\Feature\Api\UserNotification;

use App\Enums\UserNotificationStatus;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancels_pending_notification(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $this->patchJson('/api/user-notifications/'.$notification->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.status', 'canceled');

        $this->assertSame(UserNotificationStatus::Canceled, $notification->refresh()->status);
    }

    public function test_returns_detail_resource_shape(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $this->patchJson('/api/user-notifications/'.$notification->id.'/cancel')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'user_id', 'batch_id', 'channel', 'status', 'priority',
                    'subject', 'body', 'created_at', 'updated_at',
                ],
            ]);
    }

    public function test_returns_404_for_unknown_id(): void
    {
        $this->patchJson('/api/user-notifications/999999/cancel')
            ->assertStatus(404);
    }
}
