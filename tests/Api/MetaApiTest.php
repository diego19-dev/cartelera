<?php

namespace Tests\Api;

use PHPUnit\Framework\TestCase;

class MetaApiTest extends TestCase {

    public function test_meta_returns_categories(): void {
        $this->assertTrue(defined('CONTENT_CATEGORIES'));
        $this->assertArrayHasKey('news', CONTENT_CATEGORIES);
        $this->assertArrayHasKey('empresarial', PROGRAM_AREAS);
    }
}
