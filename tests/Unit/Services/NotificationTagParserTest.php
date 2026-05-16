<?php

namespace Tests\Unit\Services;

use App\Services\NotificationTagParser;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class NotificationTagParserTest extends TestCase
{
    public function test_extracts_id_from_prefixed_tag(): void
    {
        $uuid = (string) Str::uuid7();

        $this->assertSame($uuid, (new NotificationTagParser)->extractId(["notification-{$uuid}"]));
    }

    public function test_returns_null_for_empty_tag_list(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId([]));
    }

    public function test_returns_null_when_no_tag_has_prefix(): void
    {
        $uuid = (string) Str::uuid7();

        $this->assertNull((new NotificationTagParser)->extractId(["something-{$uuid}", 'other']));
    }

    public function test_returns_null_when_prefix_has_non_uuid_suffix(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId(['notification-abc']));
        $this->assertNull((new NotificationTagParser)->extractId(['notification-42']));
    }

    public function test_returns_null_when_prefix_has_no_suffix(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId(['notification-']));
    }

    public function test_uses_first_matching_prefix(): void
    {
        $first = (string) Str::uuid7();
        $second = (string) Str::uuid7();

        $this->assertSame($first, (new NotificationTagParser)->extractId([
            "notification-{$first}",
            "notification-{$second}",
        ]));
    }

    public function test_skips_non_matching_tags_before_match(): void
    {
        $uuid = (string) Str::uuid7();

        $this->assertSame($uuid, (new NotificationTagParser)->extractId(['foo', 'bar', "notification-{$uuid}"]));
    }
}
