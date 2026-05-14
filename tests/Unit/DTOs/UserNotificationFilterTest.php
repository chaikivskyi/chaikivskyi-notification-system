<?php

namespace Tests\Unit\DTOs;

use App\DTOs\UserNotificationFilter;
use App\Enums\UserNotificationChannel;
use App\Enums\UserNotificationStatus;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class UserNotificationFilterTest extends TestCase
{
    public function test_from_array_returns_null_fields_when_empty(): void
    {
        $filter = UserNotificationFilter::fromArray([]);

        $this->assertNull($filter->status);
        $this->assertNull($filter->channel);
        $this->assertNull($filter->createdFrom);
        $this->assertNull($filter->createdTo);
    }

    public function test_from_array_parses_enums(): void
    {
        $filter = UserNotificationFilter::fromArray([
            'status' => 'pending',
            'channel' => 'email',
        ]);

        $this->assertSame(UserNotificationStatus::Pending, $filter->status);
        $this->assertSame(UserNotificationChannel::Email, $filter->channel);
    }

    public function test_from_array_parses_dates(): void
    {
        $filter = UserNotificationFilter::fromArray([
            'created_from' => '2026-01-01',
            'created_to' => '2026-01-31',
        ]);

        $this->assertNotNull($filter->createdFrom, 'createdFrom must be parsed from "created_from" request key');
        $this->assertNotNull($filter->createdTo, 'createdTo must be parsed from "created_to" request key');
        $this->assertTrue(Carbon::parse('2026-01-01')->equalTo($filter->createdFrom));
        $this->assertTrue(Carbon::parse('2026-01-31')->equalTo($filter->createdTo));
    }

    public function test_from_array_returns_null_for_invalid_enum_values(): void
    {
        $filter = UserNotificationFilter::fromArray([
            'status' => 'not-a-status',
            'channel' => 'unknown',
        ]);

        $this->assertNull($filter->status);
        $this->assertNull($filter->channel);
    }
}
