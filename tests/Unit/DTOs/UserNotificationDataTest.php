<?php

namespace Tests\Unit\DTOs;

use App\DTOs\UserNotificationData;
use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationPriority;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class UserNotificationDataTest extends TestCase
{
    public function test_from_array_maps_required_fields(): void
    {
        $userId = '0190abcd-0000-7000-8000-000000000007';

        $data = UserNotificationData::fromArray([
            'user_id' => $userId,
            'channel' => 'email',
            'body' => 'Hello',
        ]);

        $this->assertSame($userId, $data->userId);
        $this->assertSame(UserNotificationChannel::Email, $data->channel);
        $this->assertSame('Hello', $data->body);
        $this->assertNull($data->subject);
        $this->assertNull($data->priority);
    }

    public function test_from_array_casts_user_id_to_string(): void
    {
        $userId = '0190abcd-0000-7000-8000-000000000042';

        $data = UserNotificationData::fromArray([
            'user_id' => $userId,
            'channel' => 'sms',
            'body' => 'x',
        ]);

        $this->assertSame($userId, $data->userId);
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

    public function test_from_array_parses_scheduled_at(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => 1,
            'channel' => 'sms',
            'body' => 'x',
            'scheduled_at' => '2026-12-01T15:00:00+00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $data->scheduledAt);
        $this->assertSame('2026-12-01 15:00:00', $data->scheduledAt->utc()->toDateTimeString());
    }

    public function test_from_array_treats_missing_scheduled_at_as_null(): void
    {
        $data = UserNotificationData::fromArray([
            'user_id' => 1,
            'channel' => 'sms',
            'body' => 'x',
        ]);

        $this->assertNull($data->scheduledAt);
    }
}
