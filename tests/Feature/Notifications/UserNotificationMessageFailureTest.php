<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserNotificationStatus;
use App\Models\UserNotification;
use App\Notifications\UserNotificationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UserNotificationMessageFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_callback_marks_pending_notification_as_failed(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $message = new UserNotificationMessage($notification);
        $message->failed(new RuntimeException('boom'));

        $this->assertSame(UserNotificationStatus::Failed, $notification->refresh()->status);
    }

    public function test_failed_callback_marks_accepted_notification_as_failed(): void
    {
        $notification = UserNotification::factory()->create(['status' => UserNotificationStatus::Accepted]);

        $message = new UserNotificationMessage($notification);
        $message->failed(new RuntimeException('boom'));

        $this->assertSame(UserNotificationStatus::Failed, $notification->refresh()->status);
    }

    public function test_failed_callback_does_not_overwrite_delivered_notification(): void
    {
        $notification = UserNotification::factory()->delivered()->create();

        $message = new UserNotificationMessage($notification);
        $message->failed(new RuntimeException('boom'));

        $this->assertSame(UserNotificationStatus::Delivered, $notification->refresh()->status);
    }

    public function test_failed_callback_does_not_overwrite_canceled_notification(): void
    {
        $notification = UserNotification::factory()->canceled()->create();

        $message = new UserNotificationMessage($notification);
        $message->failed(new RuntimeException('boom'));

        $this->assertSame(UserNotificationStatus::Canceled, $notification->refresh()->status);
    }
}
