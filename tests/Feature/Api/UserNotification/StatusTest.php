<?php

namespace Tests\Feature\Api\UserNotification;

use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_status_by_id(): void
    {
        $notification = UserNotification::factory()->delivered()->create();

        $this->getJson('/api/user-notifications/status?id='.$notification->id)
            ->assertOk()
            ->assertExactJson(['status' => 'delivered']);
    }

    public function test_returns_pending_when_batch_contains_pending(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->pending()->create();
        UserNotification::factory()->forBatch($batchId)->delivered()->create();
        UserNotification::factory()->forBatch($batchId)->failed()->create();

        $this->getJson('/api/user-notifications/status?batch_id='.$batchId)
            ->assertOk()
            ->assertExactJson(['status' => 'pending']);
    }

    public function test_returns_failed_when_batch_has_failed_but_no_pending(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->delivered()->create();
        UserNotification::factory()->forBatch($batchId)->failed()->create();

        $this->getJson('/api/user-notifications/status?batch_id='.$batchId)
            ->assertOk()
            ->assertExactJson(['status' => 'failed']);
    }

    public function test_returns_canceled_when_batch_has_canceled_and_delivered(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->canceled()->create();
        UserNotification::factory()->forBatch($batchId)->delivered()->create();

        $this->getJson('/api/user-notifications/status?batch_id='.$batchId)
            ->assertOk()
            ->assertExactJson(['status' => 'canceled']);
    }

    public function test_returns_delivered_when_batch_homogeneous(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->delivered()->count(3)->create();

        $this->getJson('/api/user-notifications/status?batch_id='.$batchId)
            ->assertOk()
            ->assertExactJson(['status' => 'delivered']);
    }

    public function test_rejects_when_neither_id_nor_batch_id_provided(): void
    {
        $this->getJson('/api/user-notifications/status')
            ->assertStatus(422);
    }

    public function test_rejects_when_both_provided(): void
    {
        $notification = UserNotification::factory()->create();
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->create();

        $this->getJson('/api/user-notifications/status?id='.$notification->id.'&batch_id='.$batchId)
            ->assertStatus(422)
            ->assertJsonValidationErrors('id');
    }

    public function test_rejects_unknown_id(): void
    {
        $this->getJson('/api/user-notifications/status?id=999999')
            ->assertStatus(422)
            ->assertJsonValidationErrors('id');
    }

    public function test_rejects_invalid_batch_id_format(): void
    {
        $this->getJson('/api/user-notifications/status?batch_id=not-a-uuid')
            ->assertStatus(422)
            ->assertJsonValidationErrors('batch_id');
    }

    public function test_rejects_unknown_batch_id(): void
    {
        $this->getJson('/api/user-notifications/status?batch_id='.Str::uuid7()->toString())
            ->assertStatus(422)
            ->assertJsonValidationErrors('batch_id');
    }
}
