<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use CenterContent;
use Database;

class CenterContentValidityTest extends TestCase {

    private array $createdIds = [];

    protected function tearDown(): void {
        $db = Database::getInstance();
        foreach ($this->createdIds as $id) {
            $db->delete('center_content', 'id = :id', ['id' => $id]);
        }
        $this->createdIds = [];
    }

    public function test_get_active_for_tv_excludes_expired(): void {
        $past = date('c', time() - 3600);
        $future = date('c', time() + 86400);

        $db = Database::getInstance();
        // Fixture expirado: insert directo (la validación de create rechaza < 1 h)
        $expiredId = $db->insert('center_content', [
            'sector' => 'tecnologia',
            'title' => 'Expired validity test',
            'description' => 'should not appear',
            'media_type' => 'text',
            'is_active' => true,
            'show_until' => $past,
            'display_order' => 9991,
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]);
        $this->createdIds[] = $expiredId;

        $activeId = CenterContent::create([
            'sector' => 'tecnologia',
            'title' => 'Active validity test',
            'description' => 'should appear',
            'media_type' => 'text',
            'is_active' => true,
            'show_until' => $future,
            'display_order' => 9992,
        ]);
        $this->createdIds[] = $activeId;

        $nullId = CenterContent::create([
            'sector' => 'tecnologia',
            'title' => 'No expiry validity test',
            'description' => 'should appear',
            'media_type' => 'text',
            'is_active' => true,
            'show_until' => '',
            'display_order' => 9993,
        ]);
        $this->createdIds[] = $nullId;

        $tv = CenterContent::getActiveForTv();
        $ids = array_column($tv, 'id');

        $this->assertNotContains($expiredId, $ids);
        $this->assertContains($activeId, $ids);
        $this->assertContains($nullId, $ids);
    }
}
