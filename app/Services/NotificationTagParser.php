<?php

namespace App\Services;

class NotificationTagParser
{
    public const PREFIX = 'notification-';

    /**
     * @param  list<string>  $tags
     */
    public function extractId(array $tags): ?int
    {
        foreach ($tags as $tag) {
            if (str_starts_with($tag, self::PREFIX)) {
                $id = substr($tag, strlen(self::PREFIX));

                return ctype_digit($id) ? (int) $id : null;
            }
        }

        return null;
    }
}
