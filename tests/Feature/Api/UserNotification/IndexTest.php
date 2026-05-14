<?php

namespace Tests\Feature\Api\UserNotification;

use App\Enums\UserNotificationChannel;
use App\Models\UserNotification;
use App\Support\Pagination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_paginated_list_with_default_per_page(): void
    {
        UserNotification::factory()->count(30)->create();

        $this->getJson('/api/user-notifications')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.per_page', Pagination::DEFAULT_PER_PAGE)
            ->assertJsonPath('meta.total', 30)
            ->assertJsonCount(Pagination::DEFAULT_PER_PAGE, 'data');
    }

    public function test_per_page_is_honored(): void
    {
        UserNotification::factory()->count(10)->create();

        $this->getJson('/api/user-notifications?per_page=5')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonCount(5, 'data');
    }

    public function test_per_page_above_max_is_rejected(): void
    {
        $this->getJson('/api/user-notifications?per_page='.(Pagination::MAX_PER_PAGE + 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_per_page_below_one_is_rejected(): void
    {
        $this->getJson('/api/user-notifications?per_page=0')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_filters_by_status(): void
    {
        UserNotification::factory()->pending()->count(2)->create();
        UserNotification::factory()->delivered()->count(3)->create();

        $this->getJson('/api/user-notifications?filters[status]=pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filters_by_channel(): void
    {
        UserNotification::factory()->forChannel(UserNotificationChannel::Sms)->count(4)->create();
        UserNotification::factory()->forChannel(UserNotificationChannel::Email)->count(1)->create();

        $this->getJson('/api/user-notifications?filters[channel]=sms')
            ->assertOk()
            ->assertJsonPath('meta.total', 4);
    }

    public function test_filters_by_date_range(): void
    {
        UserNotification::factory()->create(['created_at' => '2025-12-31']);
        UserNotification::factory()->create(['created_at' => '2026-01-01']);
        UserNotification::factory()->create(['created_at' => '2026-01-15']);
        UserNotification::factory()->create(['created_at' => '2026-02-01']);

        $this->getJson('/api/user-notifications?filters[created_from]=2026-01-01&filters[created_to]=2026-01-31')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_rejects_inverted_date_range(): void
    {
        $this->getJson('/api/user-notifications?filters[created_from]=2026-02-01&filters[created_to]=2026-01-01')
            ->assertStatus(422);
    }

    public function test_returns_expected_resource_shape(): void
    {
        UserNotification::factory()->create();

        $this->getJson('/api/user-notifications')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'batch_id', 'status', 'channel', 'created_at', 'updated_at']],
            ]);
    }

    public function test_orders_by_id_desc(): void
    {
        $a = UserNotification::factory()->create();
        $b = UserNotification::factory()->create();
        $c = UserNotification::factory()->create();

        $this->getJson('/api/user-notifications')
            ->assertOk()
            ->assertJsonPath('data.0.id', $c->id)
            ->assertJsonPath('data.1.id', $b->id)
            ->assertJsonPath('data.2.id', $a->id);
    }
}
