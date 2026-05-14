<?php

namespace App\Http\Resources\UserNotification;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserNotification $resource
 */
class ListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'batch_id' => $this->resource->batch_id,
            'status' => $this->resource->status,
            'channel' => $this->resource->channel,
            'created_at' => $this->resource->created_at,
            'update_at' => $this->resource->updated_at,
        ];
    }
}
