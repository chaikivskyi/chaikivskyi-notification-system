<?php

namespace Tests\Unit\Services;

use App\Services\NotificationTagParser;
use PHPUnit\Framework\TestCase;

class NotificationTagParserTest extends TestCase
{
    public function test_extracts_id_from_prefixed_tag(): void
    {
        $this->assertSame(42, (new NotificationTagParser)->extractId(['notification-42']));
    }

    public function test_returns_null_for_empty_tag_list(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId([]));
    }

    public function test_returns_null_when_no_tag_has_prefix(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId(['something-42', 'other']));
    }

    public function test_returns_null_when_prefix_has_non_digit_suffix(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId(['notification-abc']));
    }

    public function test_returns_null_when_prefix_has_no_suffix(): void
    {
        $this->assertNull((new NotificationTagParser)->extractId(['notification-']));
    }

    public function test_uses_first_matching_prefix(): void
    {
        $this->assertSame(1, (new NotificationTagParser)->extractId(['notification-1', 'notification-2']));
    }

    public function test_skips_non_matching_tags_before_match(): void
    {
        $this->assertSame(7, (new NotificationTagParser)->extractId(['foo', 'bar', 'notification-7']));
    }
}
