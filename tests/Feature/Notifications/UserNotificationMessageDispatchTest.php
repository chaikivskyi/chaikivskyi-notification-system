<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserNotificationStatus;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\UserNotificationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserNotificationMessageDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_for_marks_failed_and_skips_send_when_recipient_missing(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $notification = UserNotification::factory()
            ->for($user)
            ->create(['status' => UserNotificationStatus::Accepted]);

        $user->delete();
        $notification->refresh();

        UserNotificationMessage::dispatchFor($notification);

        $this->assertSame(UserNotificationStatus::Failed, $notification->refresh()->status);
        Notification::assertNothingSent();
    }

    public function test_dispatch_for_sends_notification_when_recipient_present(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $notification = UserNotification::factory()
            ->for($user)
            ->create(['status' => UserNotificationStatus::Accepted]);

        UserNotificationMessage::dispatchFor($notification);

        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
        Notification::assertSentTo($user, UserNotificationMessage::class);
    }
}
