<?php

namespace App\Services;

use Illuminate\Support\Str;

class NotificationTagParser
{
    public const PREFIX = 'notification-';

    /**
     * @param  list<string>  $tags
     */
    public function extractId(array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (str_starts_with($tag, self::PREFIX)) {
                $id = substr($tag, strlen(self::PREFIX));

                return Str::isUuid($id) ? $id : null;
            }
        }

        return null;
    }
}
