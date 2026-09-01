<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Security;

class SanitizeInputTest extends TestCase {

    public function test_sanitize_array_strips_csrf_token(): void {
        $input = [
            'title' => 'bioquimica',
            'sector' => 'salud',
            'is_active' => 'true',
            '_csrf' => 'fake-csrf-token-should-not-persist',
        ];

        $clean = Security::sanitizeArray($input);

        $this->assertArrayHasKey('title', $clean);
        $this->assertArrayHasKey('sector', $clean);
        $this->assertArrayNotHasKey('_csrf', $clean);
    }
}
