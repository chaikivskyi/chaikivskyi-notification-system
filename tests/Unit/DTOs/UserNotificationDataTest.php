<?php

namespace Tests\Unit\DTOs;

use App\DTOs\UserNotificationData;
use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use PHPUnit\Framework\TestCase;

class UserNotificationDataTest extends TestCase
{
    public function test_from_array_maps_required_fields(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => 7,
            'channel' => 'email',
            'body' => 'Hello',
        ]);

        $this->assertSame(7, $data->userId);
        $this->assertSame(UserNotificationChannel::Email, $data->channel);
        $this->assertSame('Hello', $data->body);
        $this->assertNull($data->subject);
        $this->assertNull($data->priority);
    }

    public function test_from_array_casts_user_id_to_int(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => '42',
            'channel' => 'sms',
            'body' => 'x',
        ]);

        $this->assertSame(42, $data->userId);
    }

    public function test_from_array_maps_optional_fields(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => 1,
            'channel' => 'push',
            'body' => 'hi',
            'subject' => 'subj',
            'priority' => 'high',
        ]);

        $this->assertSame('subj', $data->subject);
        $this->assertSame(UserNotificationPriority::High, $data->priority);
    }

    public function test_from_array_returns_null_priority_for_invalid_value(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => 1,
            'channel' => 'sms',
            'body' => 'x',
            'priority' => 'gibberish',
        ]);

        $this->assertNull($data->priority);
    }

    public function test_from_array_treats_empty_priority_as_null(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => 1,
            'channel' => 'sms',
            'body' => 'x',
            'priority' => '',
        ]);

        $this->assertNull($data->priority);
    }
}
