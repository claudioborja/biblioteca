<?php
// app/Controllers/UserController.php
declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\View;
use Helpers\EcuadorId;
use Middleware\CsrfMiddleware;

final class UserController extends BaseController
{
    private \Services\PdfService $pdfService;

    public function __construct()
    {
        parent::__construct();
        $this->pdfService = new \Services\PdfService();
    }

    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $users = $this->db->query(
            "SELECT
                id,
                user_number,
                name,
                email,
                email_verified_at,
                role,
                user_type,
                status,
                last_login_at,
                created_at
             FROM users
             WHERE role IN ('user', 'teacher', 'librarian')
             ORDER BY created_at DESC
             LIMIT 200"
        )->fetchAll();

        foreach ($users as &$row) {
            // El tipo visible se deriva del rol, no de user_type
            $row['type'] = match ($row['role']) {
                'librarian' => 'librarian',
                'teacher'   => 'teacher',
                default     => 'user',
            };
            $row['last_seen'] = $this->humanizeLastSeen($row['last_login_at'] ?? null);
        }
        unset($row);

        $activeCount = 0;
        $attentionCount = 0;
        $newThisMonth = 0;
        $monthPrefix = date('Y-m');

        foreach ($users as $row) {
            $status = (string) ($row['status'] ?? '');
            if ($status === 'active') {
                $activeCount++;
            }
            if (in_array($status, ['suspended', 'blocked', 'inactive'], true)) {
                $attentionCount++;
            }
            if (str_starts_with((string) ($row['created_at'] ?? ''), $monthPrefix)) {
                $newThisMonth++;
            }
        }

        return Response::html($this->view->render('admin/users/index', [
            'title'    => 'Usuarios - ' . ($this->panelSettings()['library_name'] ?? 'Biblioteca'),
            'settings' => $this->panelSettings(),
            'auth_user' => $authUser,
            'users'  => $users,
            'stats'    => [
                'total'     => count($users),
                'active'    => $activeCount,
                'attention' => $attentionCount,
                'new_month' => $newThisMonth,
            ],
            'csrf' => CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    public function create(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $isModal = $request->get('modal', '') === '1';

        // Estado post-guardado: emite postMessage al padre
        if ($isModal && $request->get('saved', '') === '1') {
            $payload     = Session::getFlash('create_user_payload', null);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body>'
                . '<script>window.parent.postMessage({type:"user-create-saved",payload:' . ($jsonPayload ?: 'null') . '},"*");</script>'
                . '</body></html>'
            );
        }

        $old      = Session::getFlash('create_user_old', []);
        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/users/create', [
            'title'    => 'Nuevo usuario — ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'old'      => is_array($old) ? $old : [],
            'csrf'     => CsrfMiddleware::token(),
        ], $isModal ? 'layouts/modal' : 'layouts/panel'));
    }

    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $name     = trim((string) $request->post('name', ''));
        $email    = mb_strtolower(trim((string) $request->post('email', '')));
        $phone    = trim((string) $request->post('phone', ''));
        $document = EcuadorId::normalizeCedula((string) $request->post('document_number', ''));
        $address  = trim((string) $request->post('address', ''));
        $userType = trim((string) $request->post('user_type', 'student'));
        $status   = trim((string) $request->post('status', 'active'));
        $emailVerified = (string) $request->post('email_verified', '0');
        $password = (string) $request->post('password', '');

        $role = match ($userType) {
            'librarian' => 'librarian',
            'teacher'   => 'teacher',
            default     => 'user',
        };
        $dbUserType = match ($userType) {
            'librarian' => 'staff',
            'teacher'   => 'teacher',
            default     => 'student',
        };

        $allowedTypes    = ['user', 'teacher', 'librarian'];
        $allowedStatuses = ['active', 'inactive', 'suspended', 'blocked'];

        $errors = [];
        if (mb_strlen($name) < 3) {
            $errors[] = 'El nombre debe tener al menos 3 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        }
        if ($document === '') {
            $errors[] = 'La cédula es obligatoria.';
        } elseif (!EcuadorId::isValidCedula($document)) {
            $errors[] = 'La cédula ecuatoriana no es válida.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!in_array($userType, $allowedTypes, true)) {
            $errors[] = 'Tipo de usuario inválido.';
        }
        if (!in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Estado inválido.';
        }
        if (!in_array($emailVerified, ['0', '1'], true)) {
            $errors[] = 'Estado de verificación de correo inválido.';
        }

        $emailCheck = $this->db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $emailCheck->execute([$email]);
        if ($emailCheck->fetch()) {
            $errors[] = 'Ya existe un usuario con ese correo electrónico.';
        }

        $documentCheck = $this->db->prepare('SELECT id FROM users WHERE document_number = ? LIMIT 1');
        $documentCheck->execute([$document]);
        if ($documentCheck->fetch()) {
            $errors[] = 'Ya existe un usuario con esa cédula.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('create_user_old', [
                'name'            => $name,
                'email'           => $email,
                'phone'           => $phone,
                'document_number' => $document,
                'address'         => $address,
                'user_type'       => $userType,
                'status'          => $status,
                'email_verified'  => $emailVerified,
            ]);
            return Response::redirect(BASE_URL . '/admin/users/create');
        }

        $userNumber = $this->db->query(
            "SELECT user_number FROM users WHERE user_number LIKE '" . date('Y') . "%' ORDER BY id DESC LIMIT 1"
        )->fetchColumn();
        $seq        = $userNumber ? ((int) substr((string) $userNumber, 5)) + 1 : 1;
        $newNumber  = date('Y') . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare(
            'INSERT INTO users
             (user_number, document_number, name, email, phone, address,
              password_hash, role, user_type, status, email_verified_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $newNumber,
            $document ?: null,
            $name,
            $email,
            $phone ?: null,
            $address ?: null,
            password_hash($password, PASSWORD_ARGON2ID),
            $role,
            $dbUserType,
            $status,
            $emailVerified === '1' ? date('Y-m-d H:i:s') : null,
        ]);

        $isModalRequest = $request->post('modal', '') === '1' || $request->get('modal', '') === '1';

        if ($isModalRequest) {
            $typeLabels = ['student' => 'Estudiante', 'teacher' => 'Docente',
                           'external' => 'Externo', 'staff' => 'Personal'];
            Session::flash('create_user_payload', [
                'id'          => (int) $this->db->lastInsertId(),
                'name'        => $name,
                'email'       => $email,
                'user_number' => $newNumber,
                'user_type'   => $userType,
                'plan'        => $typeLabels[$userType] ?? 'General',
                'status'      => $status,
                'email_verified' => $emailVerified,
                'last_seen'   => 'Sin accesos',
                'message'     => "Usuario «{$name}» creado con número {$newNumber}.",
            ]);
            return Response::redirect(BASE_URL . '/admin/users/create?modal=1&saved=1');
        }

        Session::flash('success', "Usuario «{$name}» creado con número {$newNumber}.");
        return Response::redirect(BASE_URL . '/admin/users');
    }

    public function edit(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $id;
        if ($userId <= 0) {
            Session::flash('error', 'Usuario invalido.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        if ($request->get('modal', '') === '1' && $request->get('saved', '') === '1') {
            $payload = Session::getFlash('user_edit_payload', null);
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return Response::html(
                '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Guardado</title></head><body><script>window.parent.postMessage({type:"user-edit-saved", payload:' . ($jsonPayload ?: 'null') . '},"*");</script></body></html>'
            );
        }

        $user = $this->findEditableUserById($userId);
        if ($user === null) {
            Session::flash('error', 'No se encontro el usuario solicitado.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        $old = Session::getFlash('user_edit_old', []);
        if (is_array($old) && !empty($old)) {
            $user['name']            = (string) ($old['name']            ?? $user['name']);
            $user['email']           = (string) ($old['email']           ?? $user['email']);
            $user['phone']           = (string) ($old['phone']           ?? $user['phone'] ?? '');
            $user['document_number'] = (string) ($old['document_number'] ?? $user['document_number'] ?? '');
            $user['address']         = (string) ($old['address']         ?? $user['address'] ?? '');
            $user['user_type']       = (string) ($old['user_type']       ?? $user['user_type']);
            $user['status']          = (string) ($old['status']          ?? $user['status']);
            $user['email_verified_at'] = ($old['email_verified'] ?? '0') === '1'
                ? ((string) ($user['email_verified_at'] ?? '') ?: date('Y-m-d H:i:s'))
                : null;
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('admin/users/edit', [
            'title' => 'Editar usuario - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings' => $settings,
            'auth_user' => $authUser,
            'user' => $user,
            'csrf' => CsrfMiddleware::token(),
        ], $request->get('modal', '') === '1' ? 'layouts/modal' : 'layouts/panel'));
    }

    public function show(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $id;
        if ($userId <= 0) {
            Session::flash('error', 'Usuario invalido.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        return Response::redirect(BASE_URL . '/admin/users/' . $userId . '/edit');
    }

    public function update(Request $request, string $id = ''): Response
    {
        $isModalRequest = $request->post('modal', '') === '1' || $request->get('modal', '') === '1';
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $id;
        if ($userId <= 0) {
            Session::flash('error', 'Usuario invalido.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        $user = $this->findEditableUserById($userId);
        if ($user === null) {
            Session::flash('error', 'No se encontro el usuario solicitado.');
            return Response::redirect(BASE_URL . '/admin/users');
        }

        $name     = trim((string) $request->post('name', ''));
        $email    = mb_strtolower(trim((string) $request->post('email', '')));
        $phone    = trim((string) $request->post('phone', ''));
        $document = EcuadorId::normalizeCedula((string) $request->post('document_number', ''));
        $address  = trim((string) $request->post('address', ''));
        $userType = trim((string) $request->post('user_type', 'student'));
        $status   = trim((string) $request->post('status', 'active'));
        $emailVerified = (string) $request->post('email_verified', '0');

        // Tipos permitidos: user → rol user, teacher → rol teacher, librarian → rol librarian
        $role = match ($userType) {
            'librarian' => 'librarian',
            'teacher'   => 'teacher',
            default     => 'user',
        };
        // user_type en BD: librarian se almacena como 'staff', user como 'student'
        $dbUserType = match ($userType) {
            'librarian' => 'staff',
            'teacher'   => 'teacher',
            default     => 'student',
        };

        $allowedTypes    = ['user', 'teacher', 'librarian'];
        $allowedStatuses = ['active', 'inactive', 'suspended', 'blocked'];

        $errors = [];
        if (mb_strlen($name) < 3) {
            $errors[] = 'El nombre debe tener al menos 3 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        }
        if ($document === '') {
            $errors[] = 'La cédula es obligatoria.';
        } elseif (!EcuadorId::isValidCedula($document)) {
            $errors[] = 'La cédula ecuatoriana no es válida.';
        }
        if (!in_array($userType, $allowedTypes, true)) {
            $errors[] = 'Tipo de usuario inválido.';
        }
        if (!in_array($status, $allowedStatuses, true)) {
            $errors[] = 'Estado inválido.';
        }
        if (!in_array($emailVerified, ['0', '1'], true)) {
            $errors[] = 'Estado de verificación de correo inválido.';
        }

        $emailCheck = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetch()) {
            $errors[] = 'Ya existe otro usuario con ese correo.';
        }

        $documentCheck = $this->db->prepare('SELECT id FROM users WHERE document_number = ? AND id <> ? LIMIT 1');
        $documentCheck->execute([$document, $userId]);
        if ($documentCheck->fetch()) {
            $errors[] = 'Ya existe otro usuario con esa cédula.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('user_edit_old', [
                'name'            => $name,
                'email'           => $email,
                'phone'           => $phone,
                'document_number' => $document,
                'address'         => $address,
                'user_type'       => $userType,
                'status'          => $status,
                'email_verified'  => $emailVerified,
            ]);
            return Response::redirect(
                BASE_URL . '/admin/users/' . $userId . '/edit' . ($isModalRequest ? '?modal=1' : '')
            );
        }

        $emailVerifiedAt = $emailVerified === '1'
            ? (($user['email_verified_at'] ?? null) ?: date('Y-m-d H:i:s'))
            : null;

        $update = $this->db->prepare(
            'UPDATE users
             SET name = ?, email = ?, phone = ?, document_number = ?, address = ?,
                 user_type = ?, status = ?, role = ?, email_verified_at = ?, updated_at = NOW()
             WHERE id = ?
             LIMIT 1'
        );
        $update->execute([$name, $email, $phone ?: null, $document ?: null, $address ?: null,
                          $dbUserType, $status, $role, $emailVerifiedAt, $userId]);

        if ($isModalRequest) {
            $plan = match ($userType) {
                'teacher' => 'Docente',
                'external' => 'Externo',
                'staff' => 'Personal',
                default => 'Standard',
            };

            Session::flash('user_edit_payload', [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'user_number' => (string) ($user['user_number'] ?? ''),
                'user_type' => $userType,
                'plan' => $plan,
                'status' => $status,
                'email_verified' => $emailVerified,
                'last_seen' => 'Actualizado ahora',
                'message' => 'Usuario actualizado correctamente.',
            ]);
            return Response::redirect(BASE_URL . '/admin/users/' . $userId . '/edit?modal=1&saved=1');
        }

        Session::flash('success', 'Usuario actualizado correctamente.');
        return Response::redirect(BASE_URL . '/admin/users');
    }

    public function dashboard(Request $request): Response
    {
        $user = $this->resolveAuthUser();

        if ($user === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $user['id'];

        $statsStmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM loans WHERE user_id = ? AND status = 'active') AS active_loans,
                (SELECT COUNT(*) FROM loans WHERE user_id = ? AND status = 'active' AND due_at < NOW()) AS overdue_loans,
                (SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status IN ('waiting','notified')) AS active_reservations,
                (SELECT COALESCE(SUM(GREATEST(amount - amount_paid, 0)), 0)
                   FROM fines
                  WHERE user_id = ? AND status IN ('pending','partially_paid')) AS pending_fines_amount,
                (SELECT COUNT(*) FROM fines WHERE user_id = ? AND status IN ('pending','partially_paid')) AS open_fines
        ");
        $statsStmt->execute([$userId, $userId, $userId, $userId, $userId]);
        $statsRow = $statsStmt->fetch() ?: [];

        $stats = [
            'active_loans' => (int) ($statsRow['active_loans'] ?? 0),
            'overdue_loans' => (int) ($statsRow['overdue_loans'] ?? 0),
            'active_reservations' => (int) ($statsRow['active_reservations'] ?? 0),
            'pending_fines_amount' => (float) ($statsRow['pending_fines_amount'] ?? 0),
            'open_fines' => (int) ($statsRow['open_fines'] ?? 0),
        ];

        $recentStmt = $this->db->prepare("
            SELECT l.id, l.due_at, l.status, b.title, b.authors, l.created_at
            FROM loans l
            JOIN resources b ON b.id = l.resource_id
            WHERE l.user_id = ?
            ORDER BY l.created_at DESC
            LIMIT 6
        ");
        $recentStmt->execute([$userId]);
        $recentLoans = $recentStmt->fetchAll();

        $settings = $this->panelSettings();

        return Response::html($this->view->render('account/dashboard', [
            'title'        => 'Mi cuenta — ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'     => $settings,
            'auth_user'    => [
                'id'                => $user['id'],
                'name'              => $user['name'],
                'email'             => $user['email'],
                'role'              => $user['role'],
                'user_number' => $user['user_number'],
                'last_login_at'     => $user['last_login_at'],
                'created_at'        => $user['created_at'],
                'status'            => $user['status'],
            ],
            'stats'       => $stats,
            'recentLoans' => $recentLoans,
        ], 'layouts/panel'));
    }

    public function changeStatus(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $userId = (int) $id;
        $status = trim((string) $request->post('status', ''));
        $allowed = ['active', 'inactive', 'suspended', 'blocked'];

        if ($userId <= 0 || !in_array($status, $allowed, true)) {
            return $this->jsonError('Solicitud inválida.');
        }

        $user = $this->findEditableUserById($userId);
        if ($user === null) {
            return $this->jsonError('Usuario no encontrado.');
        }

        $this->db->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1')
                 ->execute([$status, $userId]);

        $labels = ['active' => 'Activo', 'inactive' => 'Inactivo',
                   'suspended' => 'Suspendido', 'blocked' => 'Bloqueado'];

        return Response::json(['ok' => true, 'status' => $status, 'label' => $labels[$status]]);
    }

    public function changeType(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $userId   = (int) $id;
        $userType = trim((string) $request->post('user_type', ''));
        $allowed  = ['user', 'teacher', 'librarian'];

        if ($userId <= 0 || !in_array($userType, $allowed, true)) {
            return $this->jsonError('Solicitud inválida.');
        }

        $user = $this->findEditableUserById($userId);
        if ($user === null) {
            return $this->jsonError('Usuario no encontrado.');
        }

        $role = match ($userType) {
            'librarian' => 'librarian',
            'teacher'   => 'teacher',
            default     => 'user',
        };
        $dbUserType = match ($userType) {
            'librarian' => 'staff',
            'teacher'   => 'teacher',
            default     => 'student',
        };

        $oldRole = (string) ($user['role'] ?? '');
        $oldUserType = (string) ($user['user_type'] ?? '');

        $this->db->prepare('UPDATE users SET role = ?, user_type = ?, updated_at = NOW() WHERE id = ? LIMIT 1')
                 ->execute([$role, $dbUserType, $userId]);

        $labels = ['user' => 'Usuario', 'teacher' => 'Docente', 'librarian' => 'Bibliotecario'];

        $auditLogged = $this->auditRoleChange(
            actorId: (int) ($authUser['id'] ?? 0),
            targetUser: $user,
            oldRole: $oldRole,
            newRole: $role,
            oldUserType: $oldUserType,
            newUserType: $dbUserType
        );

        $emailQueued = $this->notifyRoleChangeByEmail(
            toEmail: (string) ($user['email'] ?? ''),
            toName: (string) ($user['name'] ?? ''),
            roleLabel: (string) ($labels[$userType] ?? ucfirst($userType)),
            changedBy: (string) ($authUser['name'] ?? 'Administrador')
        );

        return Response::json([
            'ok' => true,
            'user_type' => $userType,
            'label' => $labels[$userType],
            'audit_logged' => $auditLogged,
            'email_queued' => $emailQueued,
        ]);
    }

    public function resetPassword(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $userId   = (int) $id;
        $password = (string) $request->post('password', '');

        if ($userId <= 0 || mb_strlen($password) < 8) {
            return $this->jsonError('La contraseña debe tener al menos 8 caracteres.');
        }

        $user = $this->findEditableUserById($userId);
        if ($user === null) {
            return $this->jsonError('Usuario no encontrado.');
        }

        $this->db->prepare('UPDATE users SET password_hash = ?, force_password_change = 1, updated_at = NOW() WHERE id = ? LIMIT 1')
                 ->execute([password_hash($password, PASSWORD_ARGON2ID), $userId]);

        return Response::json(['ok' => true, 'message' => 'Contraseña restablecida. El usuario deberá cambiarla al iniciar sesión.']);
    }

    public function delete(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) { Session::destroy(); return Response::redirect(BASE_URL . '/login'); }

        $userId = (int) $id;
        if ($userId <= 0) {
            return $this->jsonError('Usuario inválido.');
        }

        $user = $this->findEditableUserById($userId);
        if ($user === null) {
            return $this->jsonError('Usuario no encontrado.');
        }

        // Verificar registros vinculados
        $checks = [
            'préstamos'    => 'SELECT COUNT(*) FROM loans WHERE user_id = ?',
            'reservas'     => 'SELECT COUNT(*) FROM reservations WHERE user_id = ?',
            'multas'       => 'SELECT COUNT(*) FROM fines WHERE user_id = ?',
        ];

        $blockers = [];
        foreach ($checks as $label => $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $n = (int) $stmt->fetchColumn();
            if ($n > 0) {
                $blockers[] = "{$n} {$label}";
            }
        }

        if (!empty($blockers)) {
            return $this->jsonError(
                'No se puede eliminar el usuario porque tiene registros vinculados: '
                . implode(', ', $blockers)
                . '. Para mantener trazabilidad, cambia su estado a suspendido o bloqueado en lugar de eliminarlo.'
            );
        }

        $this->db->prepare('DELETE FROM users WHERE id = ? LIMIT 1')->execute([$userId]);

        return Response::json(['ok' => true, 'message' => "Usuario «{$user['name']}» eliminado correctamente."]);
    }

    public function exportExcel(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $stmt = $this->db->query(
            "SELECT
                u.id,
                u.user_number,
                u.name,
                u.email,
                u.document_number,
                u.phone,
                u.address,
                u.role,
                u.status,
                u.force_password_change,
                u.last_login_at,
                u.last_login_ip,
                u.created_at,
                u.updated_at,
                COUNT(DISTINCT l.id)                                        AS total_loans,
                COALESCE(SUM(l.status = 'active'),  0)                      AS active_loans,
                COALESCE(SUM(l.status = 'overdue'), 0)                      AS overdue_loans,
                COUNT(DISTINCT r.id)                                        AS total_reservations,
                COALESCE(SUM(r.status = 'pending'), 0)                      AS pending_reservations,
                COUNT(DISTINCT f.id)                                        AS total_fines,
                COALESCE(SUM(CASE WHEN f.status IN ('pending','partially_paid') THEN f.amount - f.amount_paid ELSE 0 END), 0) AS unpaid_fines
             FROM users u
             LEFT JOIN loans        l ON l.user_id = u.id
             LEFT JOIN reservations r ON r.user_id = u.id
             LEFT JOIN fines        f ON f.user_id = u.id
             WHERE u.role IN ('user', 'teacher', 'librarian')
             GROUP BY u.id
             ORDER BY u.name ASC"
        );
        $users = $stmt->fetchAll();

        $settings       = $this->panelSettings();
        $libraryName    = (string) ($settings['library_name'] ?? 'Biblioteca');
        $typeLabels     = ['user' => 'Usuario', 'teacher' => 'Docente', 'librarian' => 'Bibliotecario'];
        $statusLabels   = ['active' => 'Activo', 'inactive' => 'Inactivo', 'suspended' => 'Suspendido', 'blocked' => 'Bloqueado'];

        // ── Spreadsheet ───────────────────────────────────────────────────────
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Usuarios');

        // ── Estilos reutilizables ──────────────────────────────────────────────
        $headerFill  = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF1E3A5F']];
        $headerFont  = ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10];
        $groupFill   = ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFE8EFF8']];
        $groupFont   = ['bold' => true, 'color' => ['argb' => 'FF1E3A5F'], 'size' => 9];
        $borderThin  = ['style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD0D8E4']];
        $allBorders  = ['allBorders' => $borderThin];

        // ── Fila 1: título del reporte ────────────────────────────────────────
        $sheet->mergeCells('A1:T1');
        $sheet->setCellValue('A1', $libraryName . ' · Reporte de usuarios · ' . date('d/m/Y H:i'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ── Fila 2: cabeceras de grupo ────────────────────────────────────────
        $groups = [
            'A2:F2' => 'Datos personales',
            'G2:I2' => 'Acceso y tipo',
            'J2:L2' => 'Seguridad',
            'M2:O2' => 'Préstamos',
            'P2:Q2' => 'Reservas',
            'R2:S2' => 'Multas',
            'T2:T2' => 'Auditoría',
        ];
        foreach ($groups as $range => $label) {
            $sheet->mergeCells($range);
            $sheet->setCellValue(explode(':', $range)[0], $label);
            $sheet->getStyle($range)->applyFromArray([
                'fill'      => $groupFill,
                'font'      => $groupFont,
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders'   => $allBorders,
            ]);
        }
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Fila 3: cabeceras de columna ──────────────────────────────────────
        $headers = [
            'A' => 'N° Usuario',     'B' => 'Nombre',           'C' => 'Correo',
            'D' => 'Cédula',         'E' => 'Teléfono',         'F' => 'Dirección',
            'G' => 'Tipo',           'H' => 'Estado',           'I' => 'Cambio contraseña',
            'J' => 'Último acceso',  'K' => 'IP acceso',
            'L' => 'Préstamos total','M' => 'Activos',          'N' => 'Vencidos',
            'O' => 'Reservas total', 'P' => 'Pendientes',
            'Q' => 'Multas total',   'R' => 'Monto pendiente',
            'S' => 'Creado',         'T' => 'Actualizado',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '3', $label);
        }
        $sheet->getStyle('A3:T3')->applyFromArray([
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FF2D5F8A']],
            'font'      => $headerFont,
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'wrapText'   => true],
            'borders'   => $allBorders,
        ]);
        $sheet->getRowDimension(3)->setRowHeight(36);

        // ── Filas de datos ────────────────────────────────────────────────────
        $row = 4;
        foreach ($users as $u) {
            $sheet->setCellValue('A' . $row, (string) ($u['user_number']          ?? ''));
            $sheet->setCellValue('B' . $row, (string) ($u['name']                 ?? ''));
            $sheet->setCellValue('C' . $row, (string) ($u['email']                ?? ''));
            $sheet->setCellValue('D' . $row, (string) ($u['document_number']      ?? ''));
            $sheet->setCellValue('E' . $row, (string) ($u['phone']                ?? ''));
            $sheet->setCellValue('F' . $row, (string) ($u['address']              ?? ''));
            $sheet->setCellValue('G' . $row, $typeLabels[$u['role']          ?? ''] ?? '');
            $sheet->setCellValue('H' . $row, $statusLabels[$u['status']      ?? ''] ?? '');
            $sheet->setCellValue('I' . $row, (int) ($u['force_password_change'] ?? 0) ? 'Sí' : 'No');
            $sheet->setCellValue('J' . $row, (string) ($u['last_login_at']        ?? ''));
            $sheet->setCellValue('K' . $row, (string) ($u['last_login_ip']        ?? ''));
            $sheet->setCellValue('L' . $row, (int) ($u['total_loans']             ?? 0));
            $sheet->setCellValue('M' . $row, (int) ($u['active_loans']            ?? 0));
            $sheet->setCellValue('N' . $row, (int) ($u['overdue_loans']           ?? 0));
            $sheet->setCellValue('O' . $row, (int) ($u['total_reservations']      ?? 0));
            $sheet->setCellValue('P' . $row, (int) ($u['pending_reservations']    ?? 0));
            $sheet->setCellValue('Q' . $row, (int) ($u['total_fines']             ?? 0));
            $sheet->setCellValue('R' . $row, (float) ($u['unpaid_fines']          ?? 0));
            $sheet->setCellValue('S' . $row, (string) ($u['created_at']           ?? ''));
            $sheet->setCellValue('T' . $row, (string) ($u['updated_at']           ?? ''));

            // Formato monto
            $sheet->getStyle('R' . $row)
                  ->getNumberFormat()
                  ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

            // Filas alternas
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':T' . $row)->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                               'startColor' => ['argb' => 'FFF5F7FA']],
                ]);
            }

            // Borde suave en todas las celdas de datos
            $sheet->getStyle('A' . $row . ':T' . $row)->applyFromArray(['borders' => $allBorders]);

            // Color en columna Estado
            $statusColors = [
                'Activo'     => 'FFD1FAE5',
                'Inactivo'   => 'FFF1F5F9',
                'Suspendido' => 'FFFEF3C7',
                'Bloqueado'  => 'FFFEE2E2',
            ];
            $statusVal = $statusLabels[$u['status'] ?? ''] ?? '';
            if (isset($statusColors[$statusVal])) {
                $sheet->getStyle('H' . $row)->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                               'startColor' => ['argb' => $statusColors[$statusVal]]],
                ]);
            }

            $row++;
        }

        // ── Anchos de columna ─────────────────────────────────────────────────
        $widths = [
            'A' => 14, 'B' => 28, 'C' => 30, 'D' => 14, 'E' => 14,
            'F' => 30, 'G' => 14, 'H' => 12, 'I' => 16, 'J' => 18,
            'K' => 16, 'L' => 13, 'M' => 10, 'N' => 10,
            'O' => 13, 'P' => 11,
            'Q' => 11, 'R' => 14,
            'S' => 18, 'T' => 18,
        ];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Freeze panes debajo de cabeceras
        $sheet->freezePane('A4');

        // Filtros automáticos en cabecera
        $lastRow = max($row - 1, 3);
        $sheet->setAutoFilter('A3:T' . $lastRow);

        // ── Propiedades del archivo ───────────────────────────────────────────
        $spreadsheet->getProperties()
            ->setCreator($libraryName)
            ->setTitle('Reporte de usuarios')
            ->setSubject('Usuarios ' . date('Y'))
            ->setDescription('Generado el ' . date('d/m/Y H:i'));

        // ── Salida ────────────────────────────────────────────────────────────
        $filename = 'usuarios_' . date('Ymd_His') . '.xlsx';
        ob_start();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $xlsx = ob_get_clean();

        return new Response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    public function reportPdf(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $stmt = $this->db->query(
            "SELECT
                u.user_number,
                u.name,
                u.email,
                u.document_number,
                u.phone,
                u.role,
                u.status,
                u.last_login_at,
                u.created_at,
                COUNT(DISTINCT l.id)                                         AS total_loans,
                COALESCE(SUM(l.status = 'active'),  0)                       AS active_loans,
                COALESCE(SUM(l.status = 'overdue'), 0)                       AS overdue_loans,
                COALESCE(SUM(CASE WHEN f.status IN ('pending','partially_paid') THEN f.amount - f.amount_paid ELSE 0 END), 0) AS unpaid_fines
             FROM users u
             LEFT JOIN loans l ON l.user_id = u.id
             LEFT JOIN fines f ON f.user_id = u.id
             WHERE u.role IN ('user', 'teacher', 'librarian')
             GROUP BY u.id
             ORDER BY u.name ASC"
        );
        $users = $stmt->fetchAll();

        $settings = $this->panelSettings();

        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                (string) ($u['user_number']     ?? '—'),
                (string) ($u['name']            ?? ''),
                (string) ($u['email']           ?? ''),
                (string) ($u['document_number'] ?? ''),
                (int)    ($u['total_loans']     ?? 0),
                (int)    ($u['active_loans']    ?? 0),
                (int)    ($u['overdue_loans']   ?? 0),
                number_format((float) ($u['unpaid_fines'] ?? 0), 2),
            ];
        }

        $total     = count($rows);
        $activos   = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'active'));
        $docentes  = count(array_filter($users, fn($u) => ($u['role']   ?? '') === 'teacher'));
        $biblios   = count(array_filter($users, fn($u) => ($u['role']   ?? '') === 'librarian'));

        $subtitle = "Total: {$total} usuarios · Activos: {$activos} · Docentes: {$docentes} · Bibliotecarios: {$biblios}";

        $pdf = $this->pdfService->renderSimpleTableReport([
            'library'      => (string) ($settings['library_name'] ?? 'Biblioteca'),
            'title'        => 'Informe de usuarios',
            'subtitle'     => $subtitle,
            'orientation'  => 'L',
            'generated_by' => (string) ($authUser['name'] ?? ''),
            'generated_at' => date('d/m/Y H:i'),
            'headers'     => ['N° Usuario', 'Nombre', 'Correo', 'Cédula',
                              'Préstamos', 'Activos', 'Vencidos', 'Multa pendiente'],
            'col_widths'  => [22, 58, 68, 30, 22, 18, 22, 33],
            'col_align'   => [0 => 'C', 4 => 'C', 5 => 'C', 6 => 'C', 7 => 'R'],
            'rows'        => $rows,
            'generated_at' => date('d/m/Y H:i'),
        ]);

        $filename = 'informe_usuarios_' . date('Ymd_His') . '.pdf';

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function jsonError(string $message): Response
    {
        return Response::json(['ok' => false, 'message' => $message, 'error' => $message]);
    }

    private function auditRoleChange(
        int $actorId,
        array $targetUser,
        string $oldRole,
        string $newRole,
        string $oldUserType,
        string $newUserType
    ): bool {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $oldValues = [
                'role' => $oldRole,
                'user_type' => $oldUserType,
            ];
            $newValues = [
                'role' => $newRole,
                'user_type' => $newUserType,
                'target_email' => (string) ($targetUser['email'] ?? ''),
                'target_name' => (string) ($targetUser['name'] ?? ''),
            ];

            $stmt->execute([
                $actorId > 0 ? $actorId : null,
                'user_role_changed',
                'users',
                (int) ($targetUser['id'] ?? 0),
                json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'cli'), 0, 255),
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function notifyRoleChangeByEmail(string $toEmail, string $toName, string $roleLabel, string $changedBy): bool
    {
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $template = new \Services\EmailTemplateService();
        $queue = new \Services\MailQueueService();

        $contentHtml = '<p>Hola <strong>' . htmlspecialchars($toName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>,</p>'
            . '<p>Tu rol en la plataforma fue actualizado por <strong>' . htmlspecialchars($changedBy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>.</p>'
            . '<p><strong>Nuevo rol:</strong> ' . htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>';

        $html = $template->renderSystem(
            'Cambio de rol en tu cuenta',
            'Actualización de rol',
            'Se aplicó un cambio en los permisos de tu cuenta.',
            $contentHtml,
            'Notificación automática del sistema de biblioteca.'
        );

        $text = $template->renderSystemText(
            'Actualización de rol',
            'Se aplicó un cambio en los permisos de tu cuenta.',
            'Tu nuevo rol es: ' . $roleLabel . '. Si no reconoces este cambio, contacta al administrador.',
            'Notificación automática del sistema de biblioteca.'
        );

        $queueId = null;

        try {
            $queueId = $queue->enqueue(
                $toEmail,
                $toName,
                'Tu rol en Biblioteca fue actualizado',
                $html,
                $text,
                null,
                1  // priority: critical
            );

            $this->auditEmailQueueEvent('mail_role_change_queued', [
                'to_email' => $toEmail,
                'subject' => 'Tu rol en Biblioteca fue actualizado',
                'source' => 'user_role_change',
                'queue_id' => $queueId,
            ]);

            return true;
        } catch (\Throwable $e) {
            if (is_int($queueId) && $queueId > 0) {
                try {
                    $queue->markRetry($queueId, 1, $e->getMessage(), 5);
                } catch (\Throwable) {
                    // Ignore queue retry marking errors.
                }
            }

            $this->auditEmailQueueEvent('mail_role_change_queue_failed', [
                'to_email' => $toEmail,
                'subject' => 'Tu rol en Biblioteca fue actualizado',
                'source' => 'user_role_change',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function findEditableUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, user_number, name, email, phone, document_number, address,
                                        role, user_type, status, email_verified_at, created_at, updated_at
             FROM users
             WHERE id = ?
               AND role IN ('user', 'teacher', 'librarian')
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function auditEmailQueueEvent(string $action, array $payload): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, NULL, ?, ?, ?)'
            );

            $stmt->execute([
                Session::get('auth.user_id') ?: null,
                $action,
                'emails',
                isset($payload['queue_id']) && is_numeric((string) $payload['queue_id']) ? (int) $payload['queue_id'] : null,
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'cli'), 0, 255),
            ]);
        } catch (\Throwable) {
            // Never block role change operation due to audit failures.
        }
    }

    private function humanizeLastSeen(?string $lastLoginAt): string
    {
        if (empty($lastLoginAt)) {
            return 'Sin accesos';
        }

        $ts = strtotime($lastLoginAt);
        if ($ts === false) {
            return 'Sin dato';
        }

        $diff = time() - $ts;
        if ($diff < 3600) {
            $minutes = max(1, (int) floor($diff / 60));
            return 'Hace ' . $minutes . ' min';
        }
        if ($diff < 86400) {
            return 'Hoy ' . date('H:i', $ts);
        }
        if ($diff < 172800) {
            return 'Ayer ' . date('H:i', $ts);
        }

        return date('d/m/Y H:i', $ts);
    }

    public function profile(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $authUser['id'];
        $stmt = $this->db->prepare(
            'SELECT id, user_number, name, email, document_number, phone, address,
                    birthdate, photo, role, user_type, status, email_verified_at,
                    last_login_at, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user === false) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $old = Session::getFlash('profile_old', []);
        if (is_array($old) && $old !== []) {
            foreach (['name', 'phone', 'address', 'birthdate'] as $f) {
                if (isset($old[$f])) {
                    $user[$f] = $old[$f];
                }
            }
        }

        $settings = $this->panelSettings();

        return Response::html($this->view->render('account/profile', [
            'title'     => 'Mi perfil — ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'user'      => $user,
            'csrf'      => \Middleware\CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    public function updateProfile(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $userId = (int) $authUser['id'];

        $name            = trim((string) $request->post('name', ''));
        $phone           = trim((string) $request->post('phone', ''));
        $address         = trim((string) $request->post('address', ''));
        $birthdate       = trim((string) $request->post('birthdate', ''));
        $currentPassword = (string) $request->post('current_password', '');
        $newPassword     = (string) $request->post('new_password', '');
        $confirmPassword = (string) $request->post('confirm_password', '');

        $errors = [];

        if (mb_strlen($name) < 3) {
            $errors[] = 'El nombre debe tener al menos 3 caracteres.';
        }

        if ($birthdate !== '' && strtotime($birthdate) === false) {
            $errors[] = 'La fecha de nacimiento no es válida.';
        }

        $wantsPasswordChange = $newPassword !== '' || $confirmPassword !== '';
        if ($wantsPasswordChange) {
            if ($currentPassword === '') {
                $errors[] = 'Debes ingresar tu contraseña actual para cambiarla.';
            } elseif (mb_strlen($newPassword) < 8) {
                $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'Las contraseñas nuevas no coinciden.';
            } else {
                $hashStmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
                $hashStmt->execute([$userId]);
                $row = $hashStmt->fetch();
                if ($row === false || !password_verify($currentPassword, (string) $row['password_hash'])) {
                    $errors[] = 'La contraseña actual no es correcta.';
                }
            }
        }

        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('profile_old', [
                'name'      => $name,
                'phone'     => $phone,
                'address'   => $address,
                'birthdate' => $birthdate,
            ]);
            return Response::redirect(BASE_URL . '/account/profile');
        }

        $birthdateDb = ($birthdate !== '') ? $birthdate : null;

        $stmt = $this->db->prepare(
            'UPDATE users SET name = ?, phone = ?, address = ?, birthdate = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$name, $phone !== '' ? $phone : null, $address !== '' ? $address : null, $birthdateDb, $userId]);

        if ($wantsPasswordChange) {
            $pwStmt = $this->db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
            $pwStmt->execute([password_hash($newPassword, PASSWORD_ARGON2ID), $userId]);
        }

        Session::set('auth.user_name', $name);

        Session::flash('success', 'Perfil actualizado correctamente.');
        return Response::redirect(BASE_URL . '/account/profile');
    }

}
