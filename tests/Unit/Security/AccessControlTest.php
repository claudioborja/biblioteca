<?php
declare(strict_types=1);

namespace Tests\Unit\Security;

use Enums\Permission;
use Enums\Role;
use Helpers\AccessControl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A01 — Broken Access Control.
 * Verifies role-permission boundaries: no role escalation, no privilege bleed,
 * and unknown roles are completely locked out.
 */
final class AccessControlTest extends TestCase
{
    // ── Vertical privilege escalation ────────────────────────────────────────

    #[Test]
    public function user_cannot_escalate_to_admin_permissions(): void
    {
        $adminOnly = [
            Permission::BooksDelete,
            Permission::UsersDelete,
            Permission::AdminSettings,
            Permission::AdminAudit,
            Permission::AdminBranches,
            Permission::ReportsExport,
        ];

        foreach ($adminOnly as $permission) {
            $this->assertFalse(
                AccessControl::can('user', $permission),
                "Usuario no debe tener permiso de solo-admin: {$permission->value}"
            );
        }
    }

    #[Test]
    public function teacher_cannot_access_library_management_permissions(): void
    {
        $librarianPerms = [
            Permission::BooksCreate,
            Permission::BooksEdit,
            Permission::BooksDelete,
            Permission::LoansManage,
            Permission::FinesManage,
            Permission::UsersCreate,
            Permission::UsersEdit,
            Permission::UsersDelete,
        ];

        foreach ($librarianPerms as $perm) {
            $this->assertFalse(
                AccessControl::can('teacher', $perm),
                "Teacher must not have librarian permission: {$perm->value}"
            );
        }
    }

    // ── Horizontal privilege escalation ──────────────────────────────────────

    #[Test]
    public function librarian_cannot_access_admin_only_settings(): void
    {
        $this->assertFalse(AccessControl::can('librarian', Permission::AdminSettings));
        $this->assertFalse(AccessControl::can('librarian', Permission::AdminAudit));
        $this->assertFalse(AccessControl::can('librarian', Permission::AdminBranches));
    }

    #[Test]
    public function user_cannot_access_teacher_features(): void
    {
        $this->assertFalse(AccessControl::can('user', Permission::TeacherGroups));
        $this->assertFalse(AccessControl::can('user', Permission::TeacherAssignments));
        $this->assertFalse(AccessControl::can('user', Permission::TeacherSuggestions));
    }

    // ── Guest isolation ──────────────────────────────────────────────────────

    #[Test]
    public function guest_is_limited_to_public_catalog_only(): void
    {
        $guestPerms = AccessControl::rolePermissions('guest');

        $this->assertCount(1, $guestPerms,
            'Guest must have exactly 1 permission (BooksView)');
        $this->assertContains(Permission::BooksView, $guestPerms);
    }

    #[Test]
    #[DataProvider('sensitivePermissions')]
    public function guest_cannot_perform_any_sensitive_action(Permission $perm): void
    {
        if ($perm === Permission::BooksView) {
            $this->markTestSkipped('BooksView is intentionally public');
        }
        $this->assertFalse(AccessControl::can('guest', $perm),
            "Guest must not have permission: {$perm->value}");
    }

    public static function sensitivePermissions(): array
    {
        return array_map(fn(Permission $p) => [$p], Permission::cases());
    }

    // ── Unknown/forged roles ─────────────────────────────────────────────────

    #[Test]
    #[DataProvider('injectedRoles')]
    public function forged_role_strings_have_zero_permissions(string $role): void
    {
        foreach (Permission::cases() as $perm) {
            $this->assertFalse(
                AccessControl::can($role, $perm),
                "Forged role '{$role}' must not grant permission: {$perm->value}"
            );
        }
        $this->assertEmpty(AccessControl::rolePermissions($role));
    }

    public static function injectedRoles(): array
    {
        return [
            'sql injection attempt' => ["' OR 1=1--"],
            'script injection'      => ['<script>admin</script>'],
            'null byte'             => ["admin\0"],
            'superuser'             => ['superuser'],
            'root'                  => ['root'],
            'ADMIN uppercase'       => ['ADMIN'],
            'array notation'        => ['admin[]'],
            'empty string'          => [''],
            'space padding'         => [' admin '],
        ];
    }

    // ── Permission enum completeness ──────────────────────────────────────────

    #[Test]
    public function admin_has_every_defined_permission(): void
    {
        $all   = Permission::cases();
        $admin = AccessControl::rolePermissions('admin');

        foreach ($all as $perm) {
            $this->assertContains($perm, $admin,
                "Admin must have all permissions. Missing: {$perm->value}");
        }
    }

    #[Test]
    public function permission_hierarchy_is_strictly_ordered(): void
    {
        $counts = [
            'guest'     => count(AccessControl::rolePermissions('guest')),
            'user'    => count(AccessControl::rolePermissions('user')),
            'teacher'   => count(AccessControl::rolePermissions('teacher')),
            'librarian' => count(AccessControl::rolePermissions('librarian')),
            'admin'     => count(AccessControl::rolePermissions('admin')),
        ];

        $this->assertGreaterThan($counts['guest'],     $counts['user']);
        $this->assertGreaterThan($counts['user'],    $counts['librarian']);
        $this->assertGreaterThan($counts['librarian'], $counts['admin']);
    }
}
