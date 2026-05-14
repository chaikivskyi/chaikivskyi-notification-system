<?php

namespace Tests\Feature\Repositories;

use App\DTOs\UserNotificationData;
use App\DTOs\UserNotificationFilter;
use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Enums\UserNotificationStatus;
use App\Models\User;
use App\Models\UserNotification;
use App\Repositories\UserNotificationRepository;
use App\Support\Pagination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserNotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserNotificationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->repository = new UserNotificationRepository;
    }

    public function test_list_returns_paginator_with_default_per_page(): void
    {
        UserNotification::factory()->count(30)->create();

        $page = $this->repository->list(new UserNotificationFilter);

        $this->assertSame(Pagination::DEFAULT_PER_PAGE, $page->perPage());
        $this->assertSame(30, $page->total());
        $this->assertCount(Pagination::DEFAULT_PER_PAGE, $page->items());
    }

    public function test_list_honors_custom_per_page(): void
    {
        UserNotification::factory()->count(10)->create();

        $page = $this->repository->list(new UserNotificationFilter, perPage: 5);

        $this->assertSame(5, $page->perPage());
        $this->assertCount(5, $page->items());
    }

    public function test_list_filters_by_status(): void
    {
        UserNotification::factory()->pending()->count(3)->create();
        UserNotification::factory()->delivered()->count(2)->create();

        $page = $this->repository->list(new UserNotificationFilter(
            status: UserNotificationStatus::Pending,
        ));

        $this->assertSame(3, $page->total());
    }

    public function test_list_filters_by_channel(): void
    {
        UserNotification::factory()->forChannel(UserNotificationChannel::Email)->count(2)->create();
        UserNotification::factory()->forChannel(UserNotificationChannel::Sms)->count(4)->create();

        $page = $this->repository->list(new UserNotificationFilter(
            channel: UserNotificationChannel::Sms,
        ));

        $this->assertSame(4, $page->total());
    }

    public function test_list_filters_by_date_range_inclusive_of_full_day(): void
    {
        UserNotification::factory()->create(['created_at' => '2025-12-31 23:59:59']);
        UserNotification::factory()->create(['created_at' => '2026-01-01 00:00:00']);
        UserNotification::factory()->create(['created_at' => '2026-01-15 12:00:00']);
        UserNotification::factory()->create(['created_at' => '2026-01-31 23:59:59']);
        UserNotification::factory()->create(['created_at' => '2026-02-01 00:00:00']);

        $page = $this->repository->list(new UserNotificationFilter(
            createdFrom: Carbon::parse('2026-01-01'),
            createdTo: Carbon::parse('2026-01-31'),
        ));

        $this->assertSame(3, $page->total());
    }

    public function test_list_orders_by_id_desc(): void
    {
        $a = UserNotification::factory()->create();
        $b = UserNotification::factory()->create();
        $c = UserNotification::factory()->create();

        $page = $this->repository->list(new UserNotificationFilter);

        $ids = collect($page->items())->pluck('id')->all();
        $this->assertSame([$c->id, $b->id, $a->id], $ids);
    }

    public function test_store_creates_pending_notification(): void
    {
        $user = User::factory()->create();

        $notification = $this->repository->store(new UserNotificationData(
            userId: $user->id,
            channel: UserNotificationChannel::Email,
            body: 'hello',
            subject: 'subj',
            priority: UserNotificationPriority::High,
        ));

        $this->assertSame($user->id, $notification->user_id);
        $this->assertSame(UserNotificationChannel::Email, $notification->channel);
        $this->assertSame(UserNotificationStatus::Pending, $notification->status);
        $this->assertSame(UserNotificationPriority::High, $notification->priority);
        $this->assertSame('subj', $notification->subject);
        $this->assertNull($notification->batch_id);
    }

    public function test_store_persists_provided_batch_id(): void
    {
        $user = User::factory()->create();
        $batchId = Str::uuid7()->toString();

        $notification = $this->repository->store(
            new UserNotificationData($user->id, UserNotificationChannel::Sms, 'x'),
            batchId: $batchId,
        );

        $this->assertSame($batchId, $notification->batch_id);
    }

    public function test_store_defaults_priority_to_normal_when_null(): void
    {
        $user = User::factory()->create();

        $notification = $this->repository->store(new UserNotificationData(
            userId: $user->id,
            channel: UserNotificationChannel::Email,
            body: 'x',
        ));

        $this->assertSame(UserNotificationPriority::Normal, $notification->priority);
    }

    public function test_store_bulk_inserts_all_rows_sharing_batch_id(): void
    {
        $user = User::factory()->create();

        $items = [
            new UserNotificationData($user->id, UserNotificationChannel::Email, 'a'),
            new UserNotificationData($user->id, UserNotificationChannel::Sms, 'b'),
            new UserNotificationData($user->id, UserNotificationChannel::Push, 'c'),
        ];

        $collection = $this->repository->storeBulk($items);

        $this->assertCount(3, $collection);
        $this->assertSame(3, UserNotification::count());

        $batchIds = $collection->pluck('batch_id')->unique();
        $this->assertCount(1, $batchIds);
        $this->assertNotNull($batchIds->first());
    }

    public function test_store_bulk_marks_all_pending(): void
    {
        $user = User::factory()->create();

        $this->repository->storeBulk([
            new UserNotificationData($user->id, UserNotificationChannel::Email, 'a'),
            new UserNotificationData($user->id, UserNotificationChannel::Sms, 'b'),
        ]);

        $this->assertSame(2, UserNotification::where('status', UserNotificationStatus::Pending)->count());
    }

    public function test_status_returns_status_for_single_id(): void
    {
        $notification = UserNotification::factory()->delivered()->create();

        $this->assertSame(
            UserNotificationStatus::Delivered,
            $this->repository->status(id: $notification->id),
        );
    }

    public function test_status_returns_pending_when_batch_contains_pending(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->pending()->create();
        UserNotification::factory()->forBatch($batchId)->delivered()->create();
        UserNotification::factory()->forBatch($batchId)->failed()->create();

        $this->assertSame(
            UserNotificationStatus::Pending,
            $this->repository->status(batchId: $batchId),
        );
    }

    public function test_status_returns_failed_when_batch_has_failed_but_no_pending(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->delivered()->create();
        UserNotification::factory()->forBatch($batchId)->failed()->create();

        $this->assertSame(
            UserNotificationStatus::Failed,
            $this->repository->status(batchId: $batchId),
        );
    }

    public function test_status_returns_canceled_when_batch_has_canceled_and_delivered(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->canceled()->create();
        UserNotification::factory()->forBatch($batchId)->delivered()->create();

        $this->assertSame(
            UserNotificationStatus::Canceled,
            $this->repository->status(batchId: $batchId),
        );
    }

    public function test_status_returns_single_status_when_batch_homogeneous(): void
    {
        $batchId = Str::uuid7()->toString();
        UserNotification::factory()->forBatch($batchId)->delivered()->count(3)->create();

        $this->assertSame(
            UserNotificationStatus::Delivered,
            $this->repository->status(batchId: $batchId),
        );
    }

    public function test_cancel_sets_status_to_canceled(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $this->repository->cancel($notification);

        $this->assertSame(UserNotificationStatus::Canceled, $notification->refresh()->status);
    }

    public function test_claim_for_delivery_transitions_accepted_to_pending(): void
    {
        $notification = UserNotification::factory()->create(['status' => UserNotificationStatus::Accepted]);

        $claimed = $this->repository->claimForDelivery($notification);

        $this->assertTrue($claimed);
        $this->assertSame(UserNotificationStatus::Pending, $notification->status);
        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
    }

    public function test_claim_for_delivery_is_idempotent_when_already_claimed(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $claimed = $this->repository->claimForDelivery($notification);

        $this->assertFalse($claimed);
        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
    }
}
