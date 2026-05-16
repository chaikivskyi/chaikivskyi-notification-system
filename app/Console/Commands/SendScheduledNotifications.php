<?php

namespace App\Console\Commands;

use App\Enums\UserNotificationStatus;
use App\Models\UserNotificationSchedule;
use App\Notifications\UserNotificationMessage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('notification:scheduled:send')]
#[Description('Send scheduled notifications whose scheduled_at has elapsed')]
class SendScheduledNotifications extends Command
{
    private const CHUNK_SIZE = 100;

    public function handle(): int
    {
        $dispatched = 0;

        while (true) {
            $userNotificationSchedules = DB::transaction(function () use (&$dispatched) {
                $userNotificationSchedules = UserNotificationSchedule::query()
                    ->where('scheduled_at', '<=', now())
                    ->whereHas('notification', fn (Builder $query) => $query->where('status', UserNotificationStatus::Accepted))
                    ->with('notification.user')
                    ->orderBy('id')
                    ->limit(self::CHUNK_SIZE)
                    ->lock('FOR UPDATE SKIP LOCKED')
                    ->get();

                if ($userNotificationSchedules->isEmpty()) {
                    return $userNotificationSchedules;
                }

                /** @var UserNotificationSchedule $notificationSchedule */
                foreach ($userNotificationSchedules as $notificationSchedule) {
                    UserNotificationMessage::dispatchFor($notificationSchedule->notification);
                    $dispatched++;
                }

                return $userNotificationSchedules;
            });

            if ($userNotificationSchedules->isEmpty()) {
                break;
            }
        }

        $this->info("Dispatched {$dispatched} scheduled notifications.");

        return self::SUCCESS;
    }
}
