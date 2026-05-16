<?php

namespace Tests\Unit\Notifications;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Models\UserNotification;
use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\UserNotificationMessage;
use App\Support\Queues;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use stdClass;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class UserNotificationMessageTest extends TestCase
{
    /**
     * @return array<string, array{0: UserNotificationPriority, 1: string}>
     */
    public static function priorityQueueProvider(): array
    {
        return [
            'high' => [UserNotificationPriority::High, Queues::NOTIFICATIONS_HIGH],
            'normal' => [UserNotificationPriority::Normal, Queues::NOTIFICATIONS_NORMAL],
            'low' => [UserNotificationPriority::Low, Queues::NOTIFICATIONS_LOW],
        ];
    }

    #[DataProvider('priorityQueueProvider')]
    public function test_priority_routes_to_queue(UserNotificationPriority $priority, string $expectedQueue): void
    {
        $message = new UserNotificationMessage($this->makeNotification(priority: $priority));

        $this->assertSame($expectedQueue, $message->queue);
    }

    /**
     * @return array<string, array{0: UserNotificationChannel, 1: string}>
     */
    public static function channelProvider(): array
    {
        return [
            'email' => [UserNotificationChannel::Email, 'mail'],
            'sms' => [UserNotificationChannel::Sms, SmsChannel::class],
            'push' => [UserNotificationChannel::Push, PushChannel::class],
        ];
    }

    #[DataProvider('channelProvider')]
    public function test_channel_routes_to_destination(UserNotificationChannel $channel, string $expected): void
    {
        $message = new UserNotificationMessage($this->makeNotification(channel: $channel));

        $this->assertSame([$expected], $message->via(new stdClass));
    }

    public function test_is_dispatched_after_db_commit(): void
    {
        $message = new UserNotificationMessage($this->makeNotification());

        $this->assertTrue($message->afterCommit);
    }

    public function test_retry_policy(): void
    {
        $reflector = new ReflectionClass(UserNotificationMessage::class);

        $tries = $reflector->getAttributes(Tries::class)[0]->newInstance();
        $backoff = $reflector->getAttributes(Backoff::class)[0]->newInstance();

        $this->assertSame(3, $tries->tries);
        $this->assertSame([30, 60, 120], $backoff->backoff);
    }

    public function test_to_mail_attaches_notification_tag_header(): void
    {
        $notification = $this->makeNotification();
        $uuid = '0190abcd-0000-7000-8000-000000000042';
        $notification->forceFill(['id' => $uuid]);

        $mail = (new UserNotificationMessage($notification))->toMail(new stdClass);

        $email = new Email;
        foreach ($mail->callbacks as $callback) {
            $callback($email);
        }

        $header = $email->getHeaders()->get('X-Tags');
        $this->assertNotNull($header);
        $this->assertSame("notification-{$uuid}", $header->getBody());
    }

    private function makeNotification(
        UserNotificationPriority $priority = UserNotificationPriority::Normal,
        UserNotificationChannel $channel = UserNotificationChannel::Email,
    ): UserNotification {
        $notification = new UserNotification;
        $notification->forceFill([
            'priority' => $priority->value,
            'channel' => $channel->value,
        ]);

        return $notification;
    }
}
