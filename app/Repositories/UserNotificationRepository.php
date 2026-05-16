<?php

namespace App\Repositories;

use App\DTOs\UserNotificationData;
use App\DTOs\UserNotificationFilter;
use App\Enums\UserNotificationPriority;
use App\Enums\UserNotificationStatus;
use App\Events\UserNotificationCreated;
use App\Events\UserNotificationStatusTransitioned;
use App\Http\Middleware\CorrelationId;
use App\Models\UserNotification;
use App\Models\UserNotificationSchedule;
use App\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserNotificationRepository
{
    /**
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function list(UserNotificationFilter $filter, int $perPage = Pagination::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return UserNotification::query()
            ->when($filter->status, fn (Builder $query) => $query->where('status', $filter->status))
            ->when($filter->channel, fn (Builder $query) => $query->where('channel', $filter->channel))
            ->when($filter->createdFrom, fn (Builder $query) => $query->where('created_at', '>=', $filter->createdFrom?->startOfDay()))
            ->when($filter->createdTo, fn (Builder $query) => $query->where('created_at', '<=', $filter->createdTo?->endOfDay()))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function store(UserNotificationData $data, ?string $batchId = null): UserNotification
    {
        $notification = DB::transaction(function () use ($data, $batchId): UserNotification {
            $notification = UserNotification::query()->create([
                'user_id' => $data->userId,
                'batch_id' => $batchId,
                'channel' => $data->channel,
                'subject' => $data->subject,
                'body' => $data->body,
                'priority' => $data->priority ?? UserNotificationPriority::Normal,
                'correlation_id' => $this->currentCorrelationId(),
                'is_scheduled' => $data->scheduledAt !== null,
            ]);

            if ($data->scheduledAt !== null) {
                UserNotificationSchedule::query()->create([
                    'user_notification_id' => $notification->id,
                    'scheduled_at' => $data->scheduledAt,
                ]);
            }

            return $notification;
        });

        UserNotificationCreated::dispatch($notification);

        return $notification;
    }

    /**
     * @param  array<UserNotificationData>  $items
     * @return Collection<int, UserNotification>
     */
    public function storeBulk(array $items): Collection
    {
        $batchId = Str::uuid7()->toString();
        $now = now();
        $correlationId = $this->currentCorrelationId();
        $schedules = [];

        $rows = array_map(function (UserNotificationData $item) use (&$schedules, $batchId, $now, $correlationId) {
            $id = Str::uuid7()->toString();

            if ($item->scheduledAt) {
                $schedules[] = [
                    'id' => Str::uuid7()->toString(),
                    'user_notification_id' => $id,
                    'scheduled_at' => $item->scheduledAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            return [
                'id' => $id,
                'user_id' => $item->userId,
                'batch_id' => $batchId,
                'channel' => $item->channel->value,
                'subject' => $item->subject,
                'body' => $item->body,
                'priority' => ($item->priority ?? UserNotificationPriority::Normal)->value,
                'correlation_id' => $correlationId,
                'is_scheduled' => $item->scheduledAt !== null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $items);

        $notifications = DB::transaction(function () use ($rows, $schedules, $batchId): Collection {
            UserNotification::query()->insert($rows);

            $notifications = UserNotification::query()
                ->with('user')
                ->where('batch_id', $batchId)
                ->get();

            if ($schedules !== []) {
                UserNotificationSchedule::query()->insert($schedules);
            }

            return $notifications;
        });

        foreach ($notifications as $notification) {
            UserNotificationCreated::dispatch($notification);
        }

        return $notifications;
    }

    public function status(?string $id = null, ?string $batchId = null): ?UserNotificationStatus
    {
        if ($id !== null) {
            return UserNotification::query()->findOrFail($id)->status;
        }

        $statuses = UserNotification::query()
            ->where('batch_id', $batchId)
            ->distinct()
            ->pluck('status');

        if ($statuses->isEmpty()) {
            throw new ModelNotFoundException;
        }

        if ($statuses->count() === 1) {
            return $statuses->first();
        }

        if ($statuses->contains(UserNotificationStatus::Accepted)) {
            return UserNotificationStatus::Accepted;
        }

        if ($statuses->contains(UserNotificationStatus::Pending)) {
            return UserNotificationStatus::Pending;
        }

        if ($statuses->contains(UserNotificationStatus::Failed)) {
            return UserNotificationStatus::Failed;
        }

        if ($statuses->contains(UserNotificationStatus::Canceled)) {
            return UserNotificationStatus::Canceled;
        }

        return UserNotificationStatus::Delivered;
    }

    public function cancel(UserNotification $userNotification): UserNotification
    {
        $userNotification->update(['status' => UserNotificationStatus::Canceled]);

        UserNotificationStatusTransitioned::dispatch($userNotification, UserNotificationStatus::Canceled);

        return $userNotification;
    }

    public function claimForDelivery(UserNotification $notification): bool
    {
        $claimed = UserNotification::query()
            ->whereKey($notification->id)
            ->where('status', UserNotificationStatus::Accepted)
            ->update(['status' => UserNotificationStatus::Pending]);

        if ($claimed === 0) {
            return false;
        }

        $notification->status = UserNotificationStatus::Pending;

        UserNotificationStatusTransitioned::dispatch($notification, UserNotificationStatus::Pending);

        return true;
    }

    public function markFailed(UserNotification $notification): bool
    {
        $updated = UserNotification::query()
            ->whereKey($notification->id)
            ->whereIn('status', [UserNotificationStatus::Accepted, UserNotificationStatus::Pending])
            ->update(['status' => UserNotificationStatus::Failed]);

        if ($updated === 0) {
            return false;
        }

        $notification->status = UserNotificationStatus::Failed;

        UserNotificationStatusTransitioned::dispatch($notification, UserNotificationStatus::Failed);

        return true;
    }

    public function markDelivered(UserNotification $notification): bool
    {
        $updated = UserNotification::query()
            ->whereKey($notification->id)
            ->where('status', UserNotificationStatus::Pending)
            ->update(['status' => UserNotificationStatus::Delivered]);

        if ($updated === 0) {
            return false;
        }

        $notification->status = UserNotificationStatus::Delivered;

        UserNotificationStatusTransitioned::dispatch($notification, UserNotificationStatus::Delivered);

        return true;
    }

    private function currentCorrelationId(): ?string
    {
        $value = Context::get(CorrelationId::CONTEXT_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
