<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_notification_id
 * @property Carbon $scheduled_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read UserNotification $notification
 */
#[Fillable(['user_notification_id', 'scheduled_at'])]
class UserNotificationSchedule extends Model
{
    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<UserNotification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(UserNotification::class, 'user_notification_id');
    }
}
