<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use GalleryContent;

class GalleryContentTest extends TestCase {

    public function test_get_active_for_tv_returns_array(): void {
        $items = GalleryContent::getActiveForTv();
        $this->assertIsArray($items);
    }

    public function test_all_returns_array(): void {
        $items = GalleryContent::all();
        $this->assertIsArray($items);
    }
}
