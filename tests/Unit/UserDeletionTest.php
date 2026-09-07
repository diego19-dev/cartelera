<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use User;

class UserDeletionTest extends TestCase {

    public function test_missing_user_cannot_be_deleted(): void {
        $this->assertSame(
            'Usuario no encontrado.',
            User::deletionBlockReason(null, 1, 2)
        );
    }

    public function test_cannot_delete_own_account(): void {
        $this->assertSame(
            'No puede eliminar su propia cuenta.',
            User::deletionBlockReason(['id' => 5, 'role' => 'editor'], 5, 1)
        );
    }

    public function test_cannot_delete_last_admin(): void {
        $this->assertSame(
            'No se puede eliminar al último administrador del sistema.',
            User::deletionBlockReason(['id' => 2, 'role' => 'admin'], 1, 1)
        );
    }

    public function test_can_delete_other_admin_when_more_exist(): void {
        $this->assertNull(
            User::deletionBlockReason(['id' => 2, 'role' => 'admin'], 1, 2)
        );
    }

    public function test_can_delete_editor(): void {
        $this->assertNull(
            User::deletionBlockReason(['id' => 8, 'role' => 'editor'], 1, 1)
        );
    }

    public function test_self_delete_blocked_even_for_extra_admin(): void {
        $this->assertSame(
            'No puede eliminar su propia cuenta.',
            User::deletionBlockReason(['id' => 1, 'role' => 'admin'], 1, 3)
        );
    }
}
