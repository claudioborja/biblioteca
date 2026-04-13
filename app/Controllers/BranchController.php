<?php
// app/Controllers/BranchController.php
declare(strict_types=1);

namespace Controllers;

use Core\Request;
use Core\Response;
use Core\Session;
use Middleware\CsrfMiddleware;

final class BranchController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // ── Index ────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $branches = $this->db->query(
            "SELECT
                b.id,
                b.code,
                b.name,
                b.address,
                b.phone,
                b.email,
                b.schedule,
                b.is_main,
                b.status,
                b.sort_order,
                b.created_at,
                u.name AS manager_name,
                (SELECT COUNT(*) FROM resources bk WHERE bk.branch_id = b.id) AS resources_count,
                (SELECT COUNT(*) FROM loans l WHERE l.branch_id = b.id) AS loans_count
             FROM library_branches b
             LEFT JOIN users u ON u.id = b.manager_id
             ORDER BY b.is_main DESC, b.sort_order ASC, b.name ASC"
        )->fetchAll();

        return Response::html($this->view->render('admin/branches/index', [
            'title'     => 'Sedes - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'branches'  => $branches,
            'csrf'      => CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    // ── Create ───────────────────────────────────────────────────────────

    public function create(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $settings = $this->panelSettings();

        $managers = $this->db->query(
            "SELECT id, name FROM users WHERE role IN ('admin','librarian') AND status = 'active' ORDER BY name ASC"
        )->fetchAll();

        $old = Session::getFlash('branch_form_old', [
            'code'      => '',
            'name'      => '',
            'address'   => '',
            'phone'     => '',
            'email'     => '',
            'schedule'  => '',
            'is_main'   => '0',
            'status'    => 'active',
            'sort_order'=> '0',
        ]);

        return Response::html($this->view->render('admin/branches/create', [
            'title'     => 'Nueva sede - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'managers'  => $managers,
            'old'       => $old,
            'csrf'      => CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    // ── Store ────────────────────────────────────────────────────────────

    public function store(Request $request): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $code      = strtoupper(trim((string) $request->post('code', '')));
        $name      = trim((string) $request->post('name', ''));
        $address   = trim((string) $request->post('address', ''));
        $phone     = trim((string) $request->post('phone', ''));
        $email     = trim((string) $request->post('email', ''));
        $schedule  = trim((string) $request->post('schedule', ''));
        $managerId = (int) $request->post('manager_id', 0);
        $isMain    = $request->post('is_main', '0') === '1' ? 1 : 0;
        $status    = in_array($request->post('status', 'active'), ['active', 'inactive'], true)
                     ? $request->post('status', 'active') : 'active';
        $sortOrder = (int) $request->post('sort_order', 0);

        $errors = [];
        if ($code === '') $errors[] = 'El código de sede es obligatorio.';
        if ($name === '') $errors[] = 'El nombre de la sede es obligatorio.';
        if ($address === '') $errors[] = 'La dirección es obligatoria.';

        if (empty($errors)) {
            $dup = $this->db->prepare('SELECT COUNT(*) FROM library_branches WHERE code = ? OR name = ?');
            $dup->execute([$code, $name]);
            if ((int) $dup->fetchColumn() > 0) {
                $errors[] = 'Ya existe una sede con ese código o nombre.';
            }
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('branch_form_old', $request->post());
            return Response::redirect(BASE_URL . '/admin/branches/create');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO library_branches (code, name, address, phone, email, schedule, manager_id, is_main, status, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $code,
            $name,
            $address,
            $phone ?: null,
            $email ?: null,
            $schedule ?: null,
            $managerId > 0 ? $managerId : null,
            $isMain,
            $status,
            $sortOrder,
        ]);

        Session::flash('success', "Sede «{$name}» creada correctamente.");
        return Response::redirect(BASE_URL . '/admin/branches');
    }

    // ── Edit ─────────────────────────────────────────────────────────────

    public function edit(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $branchId = (int) $id;
        if ($branchId <= 0) {
            Session::flash('error', 'Sede no válida.');
            return Response::redirect(BASE_URL . '/admin/branches');
        }

        $stmt = $this->db->prepare('SELECT * FROM library_branches WHERE id = ?');
        $stmt->execute([$branchId]);
        $branch = $stmt->fetch();

        if (!$branch) {
            Session::flash('error', 'Sede no encontrada.');
            return Response::redirect(BASE_URL . '/admin/branches');
        }

        $settings = $this->panelSettings();

        $managers = $this->db->query(
            "SELECT id, name FROM users WHERE role IN ('admin','librarian') AND status = 'active' ORDER BY name ASC"
        )->fetchAll();

        // Merge flash old values if a previous submit failed
        $old = Session::getFlash('branch_form_old', null);
        if ($old === null) {
            $old = [
                'code'       => $branch['code'],
                'name'       => $branch['name'],
                'address'    => $branch['address'],
                'phone'      => $branch['phone'] ?? '',
                'email'      => $branch['email'] ?? '',
                'schedule'   => $branch['schedule'] ?? '',
                'manager_id' => $branch['manager_id'] ?? '',
                'is_main'    => (string) $branch['is_main'],
                'status'     => $branch['status'],
                'sort_order' => (string) $branch['sort_order'],
            ];
        }

        return Response::html($this->view->render('admin/branches/edit', [
            'title'     => 'Editar sede - ' . ($settings['library_name'] ?? 'Biblioteca'),
            'settings'  => $settings,
            'auth_user' => $authUser,
            'branch'    => $branch,
            'managers'  => $managers,
            'old'       => $old,
            'csrf'      => CsrfMiddleware::token(),
        ], 'layouts/panel'));
    }

    // ── Update ───────────────────────────────────────────────────────────

    public function update(Request $request, string $id = ''): Response
    {
        $authUser = $this->resolveAuthUser();
        if ($authUser === null) {
            Session::destroy();
            return Response::redirect(BASE_URL . '/login');
        }

        $branchId = (int) $id;
        if ($branchId <= 0) {
            Session::flash('error', 'Sede no válida.');
            return Response::redirect(BASE_URL . '/admin/branches');
        }

        $code      = strtoupper(trim((string) $request->post('code', '')));
        $name      = trim((string) $request->post('name', ''));
        $address   = trim((string) $request->post('address', ''));
        $phone     = trim((string) $request->post('phone', ''));
        $email     = trim((string) $request->post('email', ''));
        $schedule  = trim((string) $request->post('schedule', ''));
        $managerId = (int) $request->post('manager_id', 0);
        $isMain    = $request->post('is_main', '0') === '1' ? 1 : 0;
        $status    = in_array($request->post('status', 'active'), ['active', 'inactive'], true)
                     ? $request->post('status', 'active') : 'active';
        $sortOrder = (int) $request->post('sort_order', 0);

        $errors = [];
        if ($code === '') $errors[] = 'El código de sede es obligatorio.';
        if ($name === '') $errors[] = 'El nombre de la sede es obligatorio.';
        if ($address === '') $errors[] = 'La dirección es obligatoria.';

        if (empty($errors)) {
            $dup = $this->db->prepare(
                'SELECT COUNT(*) FROM library_branches WHERE (code = ? OR name = ?) AND id != ?'
            );
            $dup->execute([$code, $name, $branchId]);
            if ((int) $dup->fetchColumn() > 0) {
                $errors[] = 'Ya existe otra sede con ese código o nombre.';
            }
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            Session::flash('branch_form_old', $request->post());
            return Response::redirect(BASE_URL . '/admin/branches/' . $branchId . '/edit');
        }

        $stmt = $this->db->prepare(
            'UPDATE library_branches
             SET code = ?, name = ?, address = ?, phone = ?, email = ?, schedule = ?,
                 manager_id = ?, is_main = ?, status = ?, sort_order = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $code,
            $name,
            $address,
            $phone ?: null,
            $email ?: null,
            $schedule ?: null,
            $managerId > 0 ? $managerId : null,
            $isMain,
            $status,
            $sortOrder,
            $branchId,
        ]);

        Session::flash('success', "Sede «{$name}» actualizada correctamente.");
        return Response::redirect(BASE_URL . '/admin/branches');
    }
}
