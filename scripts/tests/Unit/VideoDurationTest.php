<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VideoDurationTest extends TestCase {

    public function test_to_slide_seconds_ceils_and_enforces_min(): void {
        $this->assertSame(15, \VideoDuration::toSlideSeconds(14.2));
        $this->assertSame(5, \VideoDuration::toSlideSeconds(3.1));
    }

    public function test_to_slide_seconds_rejects_over_10_minutes(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('10 minutos');
        \VideoDuration::toSlideSeconds(601);
    }

    public function test_to_slide_seconds_allows_exactly_10_minutes(): void {
        $this->assertSame(600, \VideoDuration::toSlideSeconds(600));
    }

    public function test_clamp_slide_seconds(): void {
        $this->assertSame(5, \VideoDuration::clampSlideSeconds(1));
        $this->assertSame(600, \VideoDuration::clampSlideSeconds(999));
        $this->assertSame(42, \VideoDuration::clampSlideSeconds(42));
    }

    public function test_transcoder_finds_local_ffmpeg(): void {
        $bin = \VideoTranscoder::ffmpegBin();
        if ($bin === null) {
            $this->markTestSkipped('ffmpeg no está instalado en el sistema ni en scripts/bin');
        }
        $this->assertTrue(is_executable($bin));
    }
}
