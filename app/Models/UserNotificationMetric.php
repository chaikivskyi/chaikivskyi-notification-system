<?php

namespace App\Models;

use Database\Factories\UserNotificationMetricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_notification_id
 * @property ?Carbon $queued_at
 * @property ?Carbon $delivered_at
 * @property ?Carbon $failed_at
 * @property ?Carbon $canceled_at
 * @property-read ?UserNotification $notification
 */
#[Fillable(['user_notification_id', 'queued_at', 'delivered_at', 'failed_at', 'canceled_at'])]
class UserNotificationMetric extends Model
{
    /** @use HasFactory<UserNotificationMetricFactory> */
    use HasFactory;

    protected $primaryKey = 'user_notification_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'queued_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<UserNotification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(UserNotification::class, 'user_notification_id');
    }
}
