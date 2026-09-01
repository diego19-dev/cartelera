<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

class AuthApiTest extends TestCase {

    public function test_csrf_constant_defined(): void {
        $this->assertTrue(defined('SESSION_NAME'));
        $this->assertSame('cset_session', SESSION_NAME);
    }

    public function test_app_base_path(): void {
        $this->assertSame('/tv', APP_BASE);
    }
}
