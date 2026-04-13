<?php
declare(strict_types=1);

namespace Tests\Unit\Auth;

use Enums\Permission;
use Enums\Role;
use Helpers\AccessControl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccessControlTest extends TestCase
{
    // ── Admin ────────────────────────────────────────────────────────────────

    #[Test]
    #[DataProvider('allPermissions')]
    public function admin_has_every_permission(Permission $permission): void
    {
        $this->assertTrue(
            AccessControl::can(Role::Admin->value, $permission),
            "Admin should have permission: {$permission->value}"
        );
    }

    public static function allPermissions(): array
    {
        return array_map(fn(Permission $p) => [$p], Permission::cases());
    }

    // ── Librarian ────────────────────────────────────────────────────────────

    #[Test]
    public function librarian_can_manage_loans(): void
    {
        $this->assertTrue(AccessControl::can('librarian', Permission::LoansManage));
        $this->assertTrue(AccessControl::can('librarian', Permission::LoansCreate));
        $this->assertTrue(AccessControl::can('librarian', Permission::LoansReturn));
    }

    #[Test]
    public function librarian_cannot_delete_users(): void
    {
        $this->assertFalse(AccessControl::can('librarian', Permission::UsersDelete));
    }

    #[Test]
    public function librarian_cannot_access_admin_settings(): void
    {
        $this->assertFalse(AccessControl::can('librarian', Permission::AdminSettings));
        $this->assertFalse(AccessControl::can('librarian', Permission::AdminAudit));
        $this->assertFalse(AccessControl::can('librarian', Permission::AdminBranches));
    }

    #[Test]
    public function librarian_cannot_delete_books(): void
    {
        $this->assertFalse(AccessControl::can('librarian', Permission::BooksDelete));
    }

    // ── Teacher ──────────────────────────────────────────────────────────────

    #[Test]
    public function teacher_can_manage_groups_and_assignments(): void
    {
        $this->assertTrue(AccessControl::can('teacher', Permission::TeacherGroups));
        $this->assertTrue(AccessControl::can('teacher', Permission::TeacherAssignments));
        $this->assertTrue(AccessControl::can('teacher', Permission::TeacherSuggestions));
    }

    #[Test]
    public function teacher_cannot_manage_fines(): void
    {
        $this->assertFalse(AccessControl::can('teacher', Permission::FinesManage));
    }

    #[Test]
    public function teacher_cannot_create_or_edit_books(): void
    {
        $this->assertFalse(AccessControl::can('teacher', Permission::BooksCreate));
        $this->assertFalse(AccessControl::can('teacher', Permission::BooksEdit));
        $this->assertFalse(AccessControl::can('teacher', Permission::BooksDelete));
    }

    // ── Usuario ───────────────────────────────────────────────────────────────

    #[Test]
    public function user_can_view_books_and_create_reservations(): void
    {
        $this->assertTrue(AccessControl::can('user', Permission::BooksView));
        $this->assertTrue(AccessControl::can('user', Permission::ReservationsCreate));
        $this->assertTrue(AccessControl::can('user', Permission::FinesView));
    }

    #[Test]
    public function user_cannot_create_or_edit_books(): void
    {
        $this->assertFalse(AccessControl::can('user', Permission::BooksCreate));
        $this->assertFalse(AccessControl::can('user', Permission::BooksEdit));
        $this->assertFalse(AccessControl::can('user', Permission::BooksDelete));
    }

    #[Test]
    public function user_cannot_manage_loans_or_users(): void
    {
        $this->assertFalse(AccessControl::can('user', Permission::LoansManage));
        $this->assertFalse(AccessControl::can('user', Permission::UsersView));
    }

    #[Test]
    public function user_cannot_use_teacher_features(): void
    {
        $this->assertFalse(AccessControl::can('user', Permission::TeacherGroups));
        $this->assertFalse(AccessControl::can('user', Permission::TeacherAssignments));
    }

    // ── Guest ────────────────────────────────────────────────────────────────

    #[Test]
    public function guest_can_only_view_books(): void
    {
        $this->assertTrue(AccessControl::can('guest', Permission::BooksView));
    }

    #[Test]
    public function guest_cannot_create_reservations(): void
    {
        $this->assertFalse(AccessControl::can('guest', Permission::ReservationsCreate));
        $this->assertFalse(AccessControl::can('guest', Permission::LoansCreate));
        $this->assertFalse(AccessControl::can('guest', Permission::FinesView));
    }

    // ── Unknown role ─────────────────────────────────────────────────────────

    #[Test]
    public function unknown_role_has_no_permissions(): void
    {
        $this->assertFalse(AccessControl::can('superuser', Permission::BooksView));
        $this->assertEmpty(AccessControl::rolePermissions('superuser'));
    }

    // ── rolePermissions helper ───────────────────────────────────────────────

    #[Test]
    public function role_permissions_returns_array_of_permission_enums(): void
    {
        $perms = AccessControl::rolePermissions('user');
        $this->assertNotEmpty($perms);
        foreach ($perms as $p) {
            $this->assertInstanceOf(Permission::class, $p);
        }
    }

    #[Test]
    public function admin_has_more_permissions_than_user(): void
    {
        $adminCount  = count(AccessControl::rolePermissions('admin'));
        $userCount = count(AccessControl::rolePermissions('user'));
        $this->assertGreaterThan($userCount, $adminCount);
    }
}
