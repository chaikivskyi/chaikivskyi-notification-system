<?php

namespace App\Models;

use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use App\Enums\UserNotificationStatus;
use Database\Factories\UserNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?int $user_id
 * @property ?string $batch_id
 * @property UserNotificationChannel $channel
 * @property UserNotificationStatus $status
 * @property ?string $subject
 * @property string $body
 * @property UserNotificationPriority $priority
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read ?User $user
 */
#[Fillable(['user_id', 'batch_id', 'channel', 'status', 'subject', 'body', 'priority'])]
class UserNotification extends Model
{
    /** @use HasFactory<UserNotificationFactory> */
    use HasFactory;

    protected $casts = [
        'status' => UserNotificationStatus::class,
        'channel' => UserNotificationChannel::class,
        'priority' => UserNotificationPriority::class,
    ];

    protected $attributes = [
        'priority' => UserNotificationPriority::Normal,
        'status' => UserNotificationStatus::Accepted,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
