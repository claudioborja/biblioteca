<?php
// app/Helpers/AccessControl.php — Control de acceso basado en roles
declare(strict_types=1);

namespace Helpers;

use Enums\Permission;
use Enums\Role;

final class AccessControl
{
    private const ROLE_PERMISSIONS = [
        'admin' => [
            Permission::BooksView, Permission::BooksCreate, Permission::BooksEdit, Permission::BooksDelete,
            Permission::LoansView, Permission::LoansCreate, Permission::LoansReturn, Permission::LoansManage,
            Permission::ReservationsView, Permission::ReservationsCreate, Permission::ReservationsManage,
            Permission::FinesView, Permission::FinesManage,
            Permission::UsersView, Permission::UsersCreate, Permission::UsersEdit, Permission::UsersDelete,
            Permission::ReportsView, Permission::ReportsExport,
            Permission::NewsCreate, Permission::NewsEdit,
            Permission::AdminSettings, Permission::AdminAudit, Permission::AdminBranches,
            Permission::TeacherGroups, Permission::TeacherAssignments, Permission::TeacherSuggestions,
        ],
        'librarian' => [
            Permission::BooksView, Permission::BooksCreate, Permission::BooksEdit,
            Permission::LoansView, Permission::LoansCreate, Permission::LoansReturn, Permission::LoansManage,
            Permission::ReservationsView, Permission::ReservationsManage,
            Permission::FinesView, Permission::FinesManage,
            Permission::UsersView, Permission::UsersCreate, Permission::UsersEdit,
            Permission::ReportsView, Permission::ReportsExport,
            Permission::NewsCreate, Permission::NewsEdit,
        ],
        'teacher' => [
            Permission::BooksView,
            Permission::LoansView,
            Permission::ReservationsView, Permission::ReservationsCreate,
            Permission::FinesView,
            Permission::TeacherGroups, Permission::TeacherAssignments, Permission::TeacherSuggestions,
        ],
        'user' => [
            Permission::BooksView,
            Permission::LoansView,
            Permission::ReservationsView, Permission::ReservationsCreate,
            Permission::FinesView,
        ],
        'guest' => [
            Permission::BooksView,
        ],
    ];

    public static function can(string $role, Permission $permission): bool
    {
        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];
        return in_array($permission, $permissions, true);
    }

    public static function rolePermissions(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }
}
