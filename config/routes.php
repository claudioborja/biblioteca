<?php
// config/routes.php — Application route definitions
declare(strict_types=1);

use Core\Router;

/** @var Router $router */

// ─── Public Routes ──────────────────────────────────────────────────────────
$router->get('/', [Controllers\PublicController::class, 'home'], 'home');
$router->get('/catalog', [Controllers\PublicController::class, 'catalog'], 'catalog');
$router->get('/catalog/{id}', [Controllers\PublicController::class, 'resourceDetail'], 'resource.detail');
$router->get('/new-arrivals', [Controllers\PublicController::class, 'newAcquisitions'], 'new.arrivals');
$router->get('/news', [Controllers\NewsController::class, 'index'], 'news.index');
$router->get('/news/{slug}', [Controllers\NewsController::class, 'show'], 'news.show');
$router->get('/about', [Controllers\PublicController::class, 'about'], 'about');

// ─── Authentication ─────────────────────────────────────────────────────────
$router->get('/login', [Controllers\AuthController::class, 'showLogin'], 'login');
$router->post('/login', [Controllers\AuthController::class, 'login'], 'login.post');
$router->post('/logout', [Controllers\AuthController::class, 'logout'], 'logout');
$router->get('/register', [Controllers\AuthController::class, 'showRegister'], 'register');
$router->post('/register', [Controllers\AuthController::class, 'register'], 'register.post');
$router->get('/verify-email/{token}', [Controllers\AuthController::class, 'verifyEmail'], 'email.verify');
$router->get('/forgot-password', [Controllers\AuthController::class, 'showForgotPassword'], 'password.forgot');
$router->post('/forgot-password', [Controllers\AuthController::class, 'forgotPassword'], 'password.forgot.post');
$router->get('/reset-password/{token}', [Controllers\AuthController::class, 'showResetPassword'], 'password.reset');
$router->post('/reset-password', [Controllers\AuthController::class, 'resetPassword'], 'password.reset.post');

// ─── Search ─────────────────────────────────────────────────────────────────
$router->get('/search', [Controllers\SearchController::class, 'search'], 'search');
$router->get('/api/autocomplete', [Controllers\SearchController::class, 'autocomplete'], 'search.autocomplete');

// ─── Authenticated Area ─────────────────────────────────────────────────────
$router->group(['prefix' => '/account', 'middleware' => ['auth']], function (Router $router) {
    $router->get('', [Controllers\UserController::class, 'dashboard'], 'user.dashboard');
    $router->get('/loans', [Controllers\LoanController::class, 'myLoans'], 'user.loans');
    $router->post('/loans/{id}/renew', [Controllers\LoanController::class, 'renew'], 'user.loan.renew');
    $router->get('/reservations', [Controllers\ReservationController::class, 'myReservations'], 'user.reservations');
    $router->post('/reservations', [Controllers\ReservationController::class, 'store'], 'user.reservation.store');
    $router->post('/reservations/{id}/cancel', [Controllers\ReservationController::class, 'cancel'], 'user.reservation.cancel');
    $router->get('/fines', [Controllers\FineController::class, 'myFines'], 'user.fines');
    $router->get('/new-arrivals', [Controllers\PublicController::class, 'newAcquisitionsPrivate'], 'user.new.arrivals');
    $router->get('/profile', [Controllers\UserController::class, 'profile'], 'user.profile');
    $router->post('/profile', [Controllers\UserController::class, 'updateProfile'], 'user.profile.update');
    $router->get('/assignments', [Controllers\AssignmentController::class, 'myAssignments'], 'user.assignments');
    $router->get('/suggestions', [Controllers\SuggestionController::class, 'userIndex'], 'user.suggestions');
    $router->post('/suggestions', [Controllers\SuggestionController::class, 'userStore'], 'user.suggestion.store');
});

// ─── Teacher Panel ──────────────────────────────────────────────────────────
$router->group(['prefix' => '/teacher', 'middleware' => ['auth', 'role:teacher,admin']], function (Router $router) {
    $router->get('', [Controllers\TeacherController::class, 'dashboard'], 'teacher.dashboard');
    $router->get('/groups', [Controllers\TeacherController::class, 'groups'], 'teacher.groups');
    $router->get('/groups/create', [Controllers\TeacherController::class, 'createGroup'], 'teacher.group.create');
    $router->post('/groups', [Controllers\TeacherController::class, 'storeGroup'], 'teacher.group.store');
    $router->get('/groups/{id}', [Controllers\TeacherController::class, 'showGroup'], 'teacher.group.show');
    $router->get('/groups/{id}/edit', [Controllers\TeacherController::class, 'editGroup'], 'teacher.group.edit');
    $router->post('/groups/{id}', [Controllers\TeacherController::class, 'updateGroup'], 'teacher.group.update');
    $router->get('/groups/{id}/activity', [Controllers\TeacherController::class, 'groupActivity'], 'teacher.group.activity');
    $router->get('/groups/{id}/student/{studentId}', [Controllers\TeacherController::class, 'studentProfile'], 'teacher.student.profile');
    $router->get('/groups/{id}/report', [Controllers\TeacherController::class, 'groupReport'], 'teacher.group.report');
    $router->get('/assignments', [Controllers\AssignmentController::class, 'index'], 'teacher.assignments');
    $router->get('/assignments/create', [Controllers\AssignmentController::class, 'create'], 'teacher.assignment.create');
    $router->post('/assignments', [Controllers\AssignmentController::class, 'store'], 'teacher.assignment.store');
    $router->get('/assignments/{id}', [Controllers\AssignmentController::class, 'show'], 'teacher.assignment.show');
    $router->get('/suggestions', [Controllers\SuggestionController::class, 'index'], 'teacher.suggestions');
    $router->get('/suggestions/create', [Controllers\SuggestionController::class, 'create'], 'teacher.suggestion.create');
    $router->post('/suggestions', [Controllers\SuggestionController::class, 'store'], 'teacher.suggestion.store');
});

// ─── Admin / Librarian Panel ────────────────────────────────────────────────
$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'role:admin,librarian']], function (Router $router) {
    $router->get('', [Controllers\AdminController::class, 'dashboard'], 'admin.dashboard');

    // Resources — general
    $router->get('/resources', [Controllers\ResourceController::class, 'index'], 'admin.resources');
    $router->get('/resources/export', [Controllers\ResourceController::class, 'exportExcel'], 'admin.resources.export');
    $router->get('/resources/report/pdf', [Controllers\ResourceController::class, 'reportPdf'], 'admin.resources.report.pdf');
    $router->get('/resources/create', [Controllers\ResourceController::class, 'create'], 'admin.resource.create');
    $router->post('/resources', [Controllers\ResourceController::class, 'store'], 'admin.resource.store');
    $router->get('/resources/{id}/edit', [Controllers\ResourceController::class, 'edit'], 'admin.resource.edit');
    $router->post('/resources/{id}', [Controllers\ResourceController::class, 'update'], 'admin.resource.update');
    $router->post('/resources/{id}/deactivate', [Controllers\ResourceController::class, 'deactivate'], 'admin.resource.deactivate');
    $router->post('/resources/{id}/reactivate', [Controllers\ResourceController::class, 'reactivate'], 'admin.resource.reactivate');
    // Resources — type-based CRUD
    $router->get('/resources/type/{slug}', [Controllers\ResourceController::class, 'typeIndex'], 'admin.resources.type');
    $router->get('/resources/type/{slug}/create', [Controllers\ResourceController::class, 'typeCreate'], 'admin.resources.type.create');
    $router->post('/resources/type/{slug}', [Controllers\ResourceController::class, 'typeStore'], 'admin.resources.type.store');
    $router->get('/resources/type/{slug}/{id}/edit', [Controllers\ResourceController::class, 'typeEdit'], 'admin.resources.type.edit');
    $router->post('/resources/type/{slug}/{id}', [Controllers\ResourceController::class, 'typeUpdate'], 'admin.resources.type.update');

    // Loans
    $router->get('/loans', [Controllers\LoanController::class, 'index'], 'admin.loans');
    $router->get('/loans/create', [Controllers\LoanController::class, 'create'], 'admin.loan.create');
    $router->post('/loans', [Controllers\LoanController::class, 'store'], 'admin.loan.store');
    $router->post('/loans/{id}/return', [Controllers\LoanController::class, 'returnBook'], 'admin.loan.return');
    $router->post('/loans/{id}/renew', [Controllers\LoanController::class, 'adminRenew'], 'admin.loan.renew');
    $router->post('/loans/{id}/lost', [Controllers\LoanController::class, 'markLost'], 'admin.loan.lost');

    // Reservations
    $router->get('/reservations', [Controllers\ReservationController::class, 'index'], 'admin.reservations');
    $router->post('/reservations/{id}/convert', [Controllers\ReservationController::class, 'convertToLoan'], 'admin.reservation.convert');
    $router->post('/reservations/{id}/cancel', [Controllers\ReservationController::class, 'adminCancel'], 'admin.reservation.cancel');

    // Fines
    $router->get('/fines', [Controllers\FineController::class, 'index'], 'admin.fines');
    $router->post('/fines/{id}/payment', [Controllers\FineController::class, 'recordPayment'], 'admin.fine.payment');
    $router->post('/fines/{id}/waive', [Controllers\FineController::class, 'waive'], 'admin.fine.waive');

    // Users
    $router->get('/users', [Controllers\UserController::class, 'index'], 'admin.users');
    $router->get('/users/export', [Controllers\UserController::class, 'exportExcel'], 'admin.users.export');
    $router->get('/users/report/pdf', [Controllers\UserController::class, 'reportPdf'], 'admin.users.report.pdf');
    $router->get('/users/create', [Controllers\UserController::class, 'create'], 'admin.user.create');
    $router->post('/users', [Controllers\UserController::class, 'store'], 'admin.user.store');
    $router->get('/users/{id}', [Controllers\UserController::class, 'show'], 'admin.user.show');
    $router->get('/users/{id}/edit', [Controllers\UserController::class, 'edit'], 'admin.user.edit');
    $router->post('/users/{id}', [Controllers\UserController::class, 'update'], 'admin.user.update');
    $router->post('/users/{id}/status', [Controllers\UserController::class, 'changeStatus'], 'admin.user.status');
    $router->post('/users/{id}/type', [Controllers\UserController::class, 'changeType'], 'admin.user.type');
    $router->post('/users/{id}/reset-password', [Controllers\UserController::class, 'resetPassword'], 'admin.user.reset-password');
    $router->post('/users/{id}/delete', [Controllers\UserController::class, 'delete'], 'admin.user.delete');

    // Categories
    $router->get('/categories', [Controllers\AdminController::class, 'categories'], 'admin.categories');
    $router->post('/categories', [Controllers\AdminController::class, 'storeCategory'], 'admin.category.store');
    $router->post('/categories/{id}', [Controllers\AdminController::class, 'updateCategory'], 'admin.category.update');
    $router->post('/categories/{id}/delete', [Controllers\AdminController::class, 'deleteCategory'], 'admin.category.delete');

    // Branches
    $router->get('/branches', [Controllers\BranchController::class, 'index'], 'admin.branches');
    $router->get('/branches/create', [Controllers\BranchController::class, 'create'], 'admin.branch.create');
    $router->post('/branches', [Controllers\BranchController::class, 'store'], 'admin.branch.store');
    $router->get('/branches/{id}/edit', [Controllers\BranchController::class, 'edit'], 'admin.branch.edit');
    $router->post('/branches/{id}', [Controllers\BranchController::class, 'update'], 'admin.branch.update');

    // News
    $router->get('/news', [Controllers\NewsController::class, 'adminIndex'], 'admin.news');
    $router->get('/news/create', [Controllers\NewsController::class, 'create'], 'admin.news.create');
    $router->post('/news', [Controllers\NewsController::class, 'store'], 'admin.news.store');
    $router->get('/news/{id}/edit', [Controllers\NewsController::class, 'edit'], 'admin.news.edit');
    $router->post('/news/{id}', [Controllers\NewsController::class, 'update'], 'admin.news.update');

    // Suggestions (review)
    $router->get('/suggestions', [Controllers\SuggestionController::class, 'adminIndex'], 'admin.suggestions');
    $router->post('/suggestions/{id}/approve', [Controllers\SuggestionController::class, 'approve'], 'admin.suggestion.approve');
    $router->post('/suggestions/{id}/reject', [Controllers\SuggestionController::class, 'reject'], 'admin.suggestion.reject');
    $router->post('/suggestions/{id}/acquire', [Controllers\SuggestionController::class, 'markAcquired'], 'admin.suggestion.acquire');

    // Reports
    $router->get('/reports', [Controllers\ReportController::class, 'loans'], 'admin.reports');
    $router->get('/reports/loans', [Controllers\ReportController::class, 'loans'], 'admin.reports.loans');
    $router->get('/reports/inventory', [Controllers\ReportController::class, 'inventory'], 'admin.reports.inventory');
    $router->get('/reports/users', [Controllers\ReportController::class, 'users'], 'admin.reports.users');
    $router->get('/reports/fines', [Controllers\ReportController::class, 'fines'], 'admin.reports.fines');
    $router->get('/reports/visits', [Controllers\ReportController::class, 'visits'], 'admin.reports.visits');
    // Report exports
    $router->get('/reports/loans/export/csv', [Controllers\ReportController::class, 'exportLoansCsv'], 'admin.reports.loans.csv');
    $router->get('/reports/loans/export/pdf', [Controllers\ReportController::class, 'exportLoansPdf'], 'admin.reports.loans.pdf');
    $router->get('/reports/inventory/export/csv', [Controllers\ReportController::class, 'exportInventoryCsv'], 'admin.reports.inventory.csv');
    $router->get('/reports/inventory/export/pdf', [Controllers\ReportController::class, 'exportInventoryPdf'], 'admin.reports.inventory.pdf');
    $router->get('/reports/users/export/csv', [Controllers\ReportController::class, 'exportUsersCsv'], 'admin.reports.users.csv');
    $router->get('/reports/users/export/pdf', [Controllers\ReportController::class, 'exportUsersPdf'], 'admin.reports.users.pdf');
    $router->get('/reports/fines/export/csv', [Controllers\ReportController::class, 'exportFinesCsv'], 'admin.reports.fines.csv');
    $router->get('/reports/fines/export/pdf', [Controllers\ReportController::class, 'exportFinesPdf'], 'admin.reports.fines.pdf');
    $router->get('/reports/visits/export/csv', [Controllers\ReportController::class, 'exportVisitsCsv'], 'admin.reports.visits.csv');
    $router->get('/reports/visits/export/pdf', [Controllers\ReportController::class, 'exportVisitsPdf'], 'admin.reports.visits.pdf');
    $router->post('/reports/visits/purge', [Controllers\ReportController::class, 'purgeVisits'], 'admin.reports.visits.purge');

    // Labels and codes
    $router->get('/labels', [Controllers\BarcodeController::class, 'labels'], 'admin.labels');
    $router->post('/labels', [Controllers\BarcodeController::class, 'labels'], 'admin.labels.post');
    $router->get('/barcode/{isbn}', [Controllers\BarcodeController::class, 'barcode'], 'admin.barcode');
    $router->get('/qr/{type}/{id}', [Controllers\BarcodeController::class, 'qr'], 'admin.qr');
    $router->get('/user-card/{id}', [Controllers\BarcodeController::class, 'userCard'], 'admin.user.card');

    // Settings (Admin only)
    $router->get('/settings', [Controllers\AdminController::class, 'settings'], 'admin.settings');
    $router->post('/settings', [Controllers\AdminController::class, 'updateSettings'], 'admin.settings.update');
    $router->post('/settings/smtp-test', [Controllers\AdminController::class, 'testSmtp'], 'admin.settings.smtp_test');
    $router->get('/settings/mail-queue', [Controllers\AdminController::class, 'mailQueue'], 'admin.settings.mail_queue');
    $router->post('/settings/mail-queue/action', [Controllers\AdminController::class, 'mailQueueAction'], 'admin.settings.mail_queue.action');
    $router->get('/audit', [Controllers\AdminController::class, 'auditLogs'], 'admin.audit');
});
