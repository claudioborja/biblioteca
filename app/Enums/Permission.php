<?php
// app/Enums/Permission.php
declare(strict_types=1);

namespace Enums;

enum Permission: string
{
    // Books
    case BooksView    = 'books.view';
    case BooksCreate  = 'books.create';
    case BooksEdit    = 'books.edit';
    case BooksDelete  = 'books.delete';

    // Loans
    case LoansView    = 'loans.view';
    case LoansCreate  = 'loans.create';
    case LoansReturn  = 'loans.return';
    case LoansManage  = 'loans.manage';

    // Reservations
    case ReservationsView   = 'reservations.view';
    case ReservationsCreate = 'reservations.create';
    case ReservationsManage = 'reservations.manage';

    // Fines
    case FinesView    = 'fines.view';
    case FinesManage  = 'fines.manage';

    // Users
    case UsersView    = 'users.view';
    case UsersCreate  = 'users.create';
    case UsersEdit    = 'users.edit';
    case UsersDelete  = 'users.delete';

    // Reports
    case ReportsView  = 'reports.view';
    case ReportsExport = 'reports.export';

    // News
    case NewsCreate   = 'news.create';
    case NewsEdit     = 'news.edit';

    // Admin
    case AdminSettings = 'admin.settings';
    case AdminAudit    = 'admin.audit';
    case AdminBranches = 'admin.branches';

    // Teacher
    case TeacherGroups      = 'teacher.groups';
    case TeacherAssignments = 'teacher.assignments';
    case TeacherSuggestions = 'teacher.suggestions';
}
