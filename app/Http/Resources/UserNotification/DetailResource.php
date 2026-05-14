<?php

namespace App\Http\Resources\UserNotification;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserNotification $resource
 */
class DetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'batch_id' => $this->resource->batch_id,
            'channel' => $this->resource->channel,
            'status' => $this->resource->status,
            'priority' => $this->resource->priority,
            'subject' => $this->resource->subject,
            'body' => $this->resource->body,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
