<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AcademicUploadPermissionsTest extends TestCase {

    protected function setUp(): void {
        $_SESSION = [];
    }

    private function asProgramHead(?string $area): void {
        $_SESSION['user_id'] = 99;
        $_SESSION['user_role'] = 'program_head';
        $_SESSION['user_program_area'] = $area;
    }

    public function test_program_head_can_edit_own_area(): void {
        $this->asProgramHead('tecnologia');
        $this->assertTrue(\Auth::canEditArea('tecnologia'));
    }

    public function test_program_head_cannot_edit_foreign_area(): void {
        $this->asProgramHead('tecnologia');
        $this->assertFalse(\Auth::canEditArea('salud'));
    }

    public function test_program_head_can_edit_unassigned_area_content(): void {
        $this->asProgramHead('tecnologia');
        $this->assertTrue(\Auth::canEditArea(null));
        $this->assertTrue(\Auth::canEditArea(''));
    }

    public function test_program_head_without_area_cannot_edit(): void {
        $this->asProgramHead(null);
        $this->assertFalse(\Auth::canEditArea('tecnologia'));
        $this->assertFalse(\Auth::canEditArea(null));
    }

    public function test_editor_can_edit_any_area(): void {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'editor';
        $this->assertTrue(\Auth::canEditArea('salud'));
        $this->assertTrue(\Auth::canEditArea(null));
    }

    public function test_academic_program_area_for_program_head_create(): void {
        require_once BASE_PATH . '/src/Views/helpers.php';
        $this->asProgramHead('tecnologia');
        $this->assertSame('tecnologia', cset_academic_program_area(null));
    }

    public function test_academic_program_area_program_head_keeps_own_sector_on_edit(): void {
        require_once BASE_PATH . '/src/Views/helpers.php';
        $this->asProgramHead('tecnologia');
        $item = ['program_area' => 'salud'];
        $this->assertSame('tecnologia', cset_academic_program_area($item));
        $this->assertTrue(\Auth::canEditArea(cset_academic_program_area($item)));
    }

    public function test_academic_program_area_uses_session_when_existing_empty(): void {
        require_once BASE_PATH . '/src/Views/helpers.php';
        $this->asProgramHead('tecnologia');
        $item = ['program_area' => ''];
        $this->assertSame('tecnologia', cset_academic_program_area($item));
    }

    public function test_academic_program_area_admin_keeps_existing(): void {
        require_once BASE_PATH . '/src/Views/helpers.php';
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_program_area'] = null;
        $item = ['program_area' => 'salud'];
        $this->assertSame('salud', cset_academic_program_area($item));
    }
}
