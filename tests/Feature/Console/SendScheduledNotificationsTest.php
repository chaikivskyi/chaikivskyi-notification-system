<?php

namespace Tests\Feature\Console;

use App\Enums\UserNotificationStatus;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserNotificationSchedule;
use App\Notifications\UserNotificationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendScheduledNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_dispatches_due_notifications_and_claims_them(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeScheduledNotification($user, scheduledAt: now()->subMinute());

        $this->assertSame(0, Artisan::call('notification:scheduled:send'));

        Notification::assertSentTo($user, UserNotificationMessage::class);
        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
    }

    public function test_does_not_redispatch_already_pending_notifications(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeScheduledNotification($user, scheduledAt: now()->subMinute());

        $this->assertSame(0, Artisan::call('notification:scheduled:send'));
        Notification::assertSentToTimes($user, UserNotificationMessage::class, 1);

        $this->assertSame(0, Artisan::call('notification:scheduled:send'));
        Notification::assertSentToTimes($user, UserNotificationMessage::class, 1);

        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
    }

    public function test_skips_notifications_whose_scheduled_at_is_in_the_future(): void
    {
        $user = User::factory()->create();
        $notification = $this->makeScheduledNotification($user, scheduledAt: now()->addMinute());

        $this->assertSame(0, Artisan::call('notification:scheduled:send'));

        Notification::assertNothingSent();
        $notification->refresh();
        $this->assertTrue($notification->is_scheduled);
        $this->assertSame(UserNotificationStatus::Accepted, $notification->status);
    }

    public function test_skips_canceled_scheduled_notifications(): void
    {
        $user = User::factory()->create();
        $this->makeScheduledNotification(
            $user,
            scheduledAt: now()->subMinute(),
            status: UserNotificationStatus::Canceled,
        );

        $this->assertSame(0, Artisan::call('notification:scheduled:send'));

        Notification::assertNothingSent();
    }

    public function test_respects_limit_option(): void
    {
        $user = User::factory()->create();
        $this->makeScheduledNotification($user, scheduledAt: now()->subMinutes(3));
        $this->makeScheduledNotification($user, scheduledAt: now()->subMinutes(2));
        $this->makeScheduledNotification($user, scheduledAt: now()->subMinute());

        $this->assertSame(0, Artisan::call('notification:scheduled:send', ['--limit' => 2]));

        $this->assertSame(2, UserNotification::where('status', UserNotificationStatus::Pending)->count());
        $this->assertSame(1, UserNotification::where('status', UserNotificationStatus::Accepted)->count());
        Notification::assertSentToTimes($user, UserNotificationMessage::class, 2);
    }

    public function test_processes_all_due_notifications_across_chunks_when_no_limit(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->makeScheduledNotification($user, scheduledAt: now()->subMinutes(10 - $i));
        }

        $this->assertSame(0, Artisan::call('notification:scheduled:send'));

        $this->assertSame(5, UserNotification::where('status', UserNotificationStatus::Pending)->count());
        Notification::assertSentToTimes($user, UserNotificationMessage::class, 5);
    }

    private function makeScheduledNotification(
        User $user,
        Carbon $scheduledAt,
        UserNotificationStatus $status = UserNotificationStatus::Accepted,
    ): UserNotification {
        $notification = UserNotification::factory()
            ->for($user)
            ->state([
                'status' => $status,
                'is_scheduled' => true,
            ])
            ->create();

        UserNotificationSchedule::create([
            'user_notification_id' => $notification->id,
            'scheduled_at' => $scheduledAt,
        ]);

        return $notification;
    }
}
