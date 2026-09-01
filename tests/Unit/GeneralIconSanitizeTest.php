<?php

namespace Tests\Unit;

use GeneralContent;
use PHPUnit\Framework\TestCase;

class GeneralIconSanitizeTest extends TestCase {

    public function test_valid_icon_is_kept(): void {
        $this->assertSame('dollar-sign', GeneralContent::sanitizeIcon('dollar-sign'));
    }

    public function test_empty_or_invalid_becomes_null(): void {
        $this->assertNull(GeneralContent::sanitizeIcon(''));
        $this->assertNull(GeneralContent::sanitizeIcon('   '));
        $this->assertNull(GeneralContent::sanitizeIcon('not-a-real-icon'));
        $this->assertNull(GeneralContent::sanitizeIcon(null));
    }
}
