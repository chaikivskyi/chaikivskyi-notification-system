<?php

namespace App\Notifications;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Enums\UserNotificationStatus;
use App\Models\UserNotification;
use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Middleware\PushCorrelationContext;
use App\Repositories\UserNotificationRepository;
use App\Support\Queues;
use App\Support\RateLimiters;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Backoff([30, 60, 120])]
#[Tries(3)]
class UserNotificationMessage extends Notification implements ShouldQueue
{
    use Queueable;

    private const string DEFAULT_SUBJECT = 'Notification';

    public function __construct(public UserNotification $notification)
    {
        $this->onQueue(match ($notification->priority) {
            UserNotificationPriority::High => Queues::NOTIFICATIONS_HIGH,
            UserNotificationPriority::Normal => Queues::NOTIFICATIONS_NORMAL,
            UserNotificationPriority::Low => Queues::NOTIFICATIONS_LOW,
        });

        $this->afterCommit();
    }

    public static function dispatchFor(UserNotification $notification): void
    {
        $repository = app(UserNotificationRepository::class);

        if (! $notification->user) {
            $repository->markFailed($notification);
            Log::warning('Notification has no recipient.', ['notification' => $notification->id]);

            return;
        }

        if (! $repository->claimForDelivery($notification)) {
            Log::warning('The notification is not suitable for delivery.', [
                'notification' => $notification->id,
                'status' => $notification->status->value,
            ]);

            return;
        }

        $notification->user->notify(new self($notification));
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        app(UserNotificationRepository::class)->markFailed($this->notification);
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [match ($this->notification->channel) {
            UserNotificationChannel::Email => 'mail',
            UserNotificationChannel::Sms => SmsChannel::class,
            UserNotificationChannel::Push => PushChannel::class,
        }];
    }

    public function shouldSend(object $notifiable): bool
    {
        $fresh = $this->notification->fresh();

        if ($fresh === null || $fresh->status === UserNotificationStatus::Canceled) {
            Log::info('Skipping canceled or missing notification.', [
                'notification' => $this->notification->id,
                'channel' => $this->notification->channel->value,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @return list<object>
     */
    public function middleware(object $notifiable, string $channel): array
    {
        return [
            new PushCorrelationContext($this->notification->correlation_id),
            new RateLimited($this->limiterFor()),
            (new WithoutOverlapping("user-notification:{$this->notification->id}"))->dontRelease(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $notificationId = (string) $this->notification->id;

        return (new MailMessage)
            ->line($this->notification->body)
            ->subject($this->notification->subject ?? self::DEFAULT_SUBJECT)
            ->withSymfonyMessage(function ($message) use ($notificationId): void {
                $message->getHeaders()->addTextHeader('X-Tags', "notification-{$notificationId}");
            });
    }

    public function toSms(object $notifiable): string
    {
        return $this->notification->body;
    }

    /**
     * @return array<string, ?string>
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->notification->subject ?? self::DEFAULT_SUBJECT,
            'body' => $this->notification->body,
        ];
    }

    private function limiterFor(): string
    {
        return RateLimiters::USER_NOTIFICATIONS_PREFIX.':'.$this->notification->channel->value;
    }
}
