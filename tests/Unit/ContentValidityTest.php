<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ContentValidity;
use InvalidArgumentException;

class ContentValidityTest extends TestCase {

    public function test_parse_empty_returns_null(): void {
        $this->assertNull(ContentValidity::parseDateTime(null));
        $this->assertNull(ContentValidity::parseDateTime(''));
        $this->assertNull(ContentValidity::parseDateTime('   '));
    }

    public function test_parse_valid_iso(): void {
        $out = ContentValidity::parseDateTime('2026-12-01T15:00:00+00:00');
        $this->assertNotNull($out);
        $this->assertNotFalse(strtotime($out));
    }

    public function test_assert_rejects_less_than_one_hour(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('1 hora');
        $iso = date('c', time() + 1800);
        ContentValidity::assertShowUntil($iso);
    }

    public function test_assert_rejects_more_than_six_months(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('6 meses');
        $iso = (new \DateTimeImmutable('now'))->modify('+7 months')->format('c');
        ContentValidity::assertShowUntil($iso);
    }

    public function test_assert_accepts_three_days(): void {
        $iso = date('c', time() + 3 * 86400);
        ContentValidity::assertShowUntil($iso);
        $this->assertTrue(true);
    }

    public function test_assert_allows_unchanged_short_remaining(): void {
        $iso = date('c', time() + 900);
        ContentValidity::assertShowUntil($iso, $iso);
        $this->assertTrue(true);
    }

    public function test_apply_show_window_clears_empty(): void {
        $data = ['show_until' => '', 'show_from' => ''];
        ContentValidity::applyShowWindow($data);
        $this->assertNull($data['show_until']);
        $this->assertNull($data['show_from']);
    }

    public function test_add_duration_days(): void {
        $from = new \DateTimeImmutable('2026-08-01 12:00:00');
        $until = ContentValidity::addDuration(3, 'days', $from);
        $this->assertSame('2026-08-04', $until->format('Y-m-d'));
    }

    public function test_add_duration_rejects_bad_unit(): void {
        $this->expectException(InvalidArgumentException::class);
        ContentValidity::addDuration(1, 'weeks');
    }

    public function test_apply_show_window_sets_valid_until(): void {
        $iso = date('c', time() + 2 * 86400);
        $data = ['show_until' => $iso];
        ContentValidity::applyShowWindow($data);
        $this->assertNotNull($data['show_until']);
    }
}
