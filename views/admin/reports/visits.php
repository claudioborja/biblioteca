<?php
// views/admin/reports/visits.php
$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$rolLabel = static fn(string $r): array => match ($r) {
    'admin'     => ['Admin',        'bg-red-100 text-red-700'],
    'librarian' => ['Bibliotecario','bg-purple-100 text-purple-700'],
    'teacher'   => ['Docente',      'bg-blue-100 text-blue-700'],
    'user'      => ['Socio',        'bg-emerald-100 text-emerald-700'],
    default     => ['Anónimo',      'bg-slate-100 text-slate-600'],
};
?>

<style>
@import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
</style>

<section class="p-6 lg:p-8">
    <?php $currentReport = 'visits'; require __DIR__ . '/_subnav.php'; ?>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="label-sm">Reportes</p>
            <h1 class="headline-lg text-on-surface">Visitas</h1>
            <p class="body-md mt-1 text-on-surface-muted">Registro de visitas únicas por visitante y día a la página principal.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/admin/reports/visits/export/csv"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-emerald-700">
                <iconify-icon icon="mdi:file-excel-box"></iconify-icon> Excel
            </a>
            <a href="<?= BASE_URL ?>/admin/reports/visits/export/pdf"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-ambient transition-colors hover:bg-red-700">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon> PDF
            </a>
            <?php if (($auth_user['role'] ?? '') === 'admin'): ?>
            <button type="button" id="btn-purge-visits"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-100 border border-outline-variant px-4 py-2 text-sm font-semibold text-red-700 shadow-ambient transition-colors hover:bg-red-50 hover:border-red-300">
                <i class="bi bi-trash3 text-sm"></i> Limpiar
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-5 flex gap-1 rounded-xl bg-surface-container-low p-1 w-fit border border-outline-variant/60">
        <button type="button" data-tab="registros"
                class="v-tab rounded-lg px-4 py-2 text-sm font-semibold transition-colors bg-white text-primary shadow-sm">
            <i class="bi bi-table mr-1.5"></i>Registros
        </button>
        <button type="button" data-tab="kpis"
                class="v-tab rounded-lg px-4 py-2 text-sm font-semibold transition-colors text-on-surface-subtle hover:text-on-surface">
            <i class="bi bi-bar-chart-line mr-1.5"></i>KPIs
        </button>
    </div>

    <!-- Tab: KPIs -->
    <div id="tab-kpis" class="hidden">
        <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient flex flex-col gap-1">
                <p class="label-sm text-on-surface-subtle uppercase tracking-wide">Total acumulado</p>
                <p class="mt-1 text-5xl font-extrabold font-display leading-none text-on-surface"><?= number_format((int)($kpis['total'] ?? 0)) ?></p>
                <p class="text-xs text-on-surface-subtle mt-1">visitas registradas</p>
            </article>
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient flex flex-col gap-1">
                <p class="label-sm text-on-surface-subtle uppercase tracking-wide">Últimos 30 días</p>
                <p class="mt-1 text-5xl font-extrabold font-display leading-none text-primary"><?= number_format((int)($kpis['visits_30d'] ?? 0)) ?></p>
                <p class="text-xs text-on-surface-subtle mt-1">visitas recientes</p>
            </article>
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient flex flex-col gap-1">
                <p class="label-sm text-on-surface-subtle uppercase tracking-wide">Visitantes únicos (30 d)</p>
                <p class="mt-1 text-5xl font-extrabold font-display leading-none text-emerald-700"><?= number_format((int)($kpis['unique_users_30d'] ?? 0)) ?></p>
                <p class="text-xs text-on-surface-subtle mt-1">por usuario/IP distintos</p>
            </article>
            <article class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient flex flex-col gap-1">
                <p class="label-sm text-on-surface-subtle uppercase tracking-wide">Hoy</p>
                <p class="mt-1 text-5xl font-extrabold font-display leading-none text-amber-700"><?= number_format((int)($kpis['visits_today'] ?? 0)) ?></p>
                <?php if (($kpis['last_visit'] ?? '—') !== '—'): ?>
                <p class="text-xs text-on-surface-subtle mt-1">Última: <?= $e(substr((string)$kpis['last_visit'], 0, 16)) ?></p>
                <?php else: ?>
                <p class="text-xs text-on-surface-subtle mt-1">sin visitas hoy</p>
                <?php endif; ?>
            </article>
        </div>
        <!-- Resumen texto -->
        <div class="rounded-2xl border border-outline-variant/60 bg-white p-6 shadow-ambient">
            <h3 class="text-sm font-semibold text-on-surface mb-3">Resumen</h3>
            <ul class="space-y-2 text-sm text-on-surface-muted">
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-on-surface inline-block"></span>
                    <span>Total histórico: <strong class="text-on-surface"><?= number_format((int)($kpis['total'] ?? 0)) ?></strong> visitas registradas en la base de datos.</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-primary inline-block"></span>
                    <span>En los últimos 30 días se registraron <strong class="text-primary"><?= number_format((int)($kpis['visits_30d'] ?? 0)) ?></strong> visitas.</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-600 inline-block"></span>
                    <span><strong class="text-emerald-700"><?= number_format((int)($kpis['unique_users_30d'] ?? 0)) ?></strong> visitantes únicos (por usuario o IP) en los últimos 30 días.</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-amber-500 inline-block"></span>
                    <span>Hoy se han registrado <strong class="text-amber-700"><?= number_format((int)($kpis['visits_today'] ?? 0)) ?></strong> visitas.
                        <?php if (($kpis['last_visit'] ?? '—') !== '—'): ?>
                        Última a las <strong><?= $e(substr((string)$kpis['last_visit'], 11, 5)) ?></strong>.
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab: Registros -->
    <div id="tab-registros">

    <!-- Filter bar -->
    <div class="mb-5 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient">
        <div class="grid gap-3 md:grid-cols-5">
            <div class="md:col-span-2">
                <label for="v-search" class="label-sm">Buscar</label>
                <input id="v-search" type="text" placeholder="Usuario, correo, IP, referencia…"
                       class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface placeholder-on-surface-subtle focus:border-primary focus:outline-none">
            </div>
            <div>
                <label for="v-rol" class="label-sm">Rol</label>
                <select id="v-rol" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    <option value="all">Todos</option>
                    <option value="admin">Admin</option>
                    <option value="librarian">Bibliotecario</option>
                    <option value="teacher">Docente</option>
                    <option value="user">Socio</option>
                    <option value="guest">Anónimo</option>
                </select>
            </div>
            <div>
                <label for="v-date" class="label-sm">Fecha</label>
                <input id="v-date" type="date"
                       class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
            </div>
            <div>
                <label for="v-page-size" class="label-sm">Filas</label>
                <select id="v-page-size" class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2.5 text-sm focus:border-primary focus:outline-none">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table id="v-table" class="min-w-full text-left">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-subtle">
                    <tr>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="date_sort" class="v-sort inline-flex items-center gap-1 hover:text-primary">
                                Fecha <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="usuario" class="v-sort inline-flex items-center gap-1 hover:text-primary">
                                Usuario <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <button type="button" data-sort="rol" class="v-sort inline-flex items-center gap-1 hover:text-primary">
                                Rol <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold hidden md:table-cell">
                            <button type="button" data-sort="ip" class="v-sort inline-flex items-center gap-1 hover:text-primary">
                                IP <span class="text-[10px] opacity-70">⇅</span>
                            </button>
                        </th>
                        <th class="px-4 py-3 font-semibold hidden lg:table-cell">Referencia</th>
                    </tr>
                </thead>
                <tbody id="v-table-body" class="divide-y divide-outline-variant/50 text-sm">
                    <?php foreach ($recent as $r):
                        $rl    = $rolLabel((string)($r['rol'] ?? 'guest'));
                        $fecha = substr((string)($r['created_at'] ?? ''), 0, 16);
                        $datePart = substr($fecha, 0, 10);
                        $ts    = strtotime((string)($r['created_at'] ?? '')) ?: 0;
                        $usr   = (string)($r['usuario'] ?? 'Anónimo');
                        $mail  = (string)($r['correo'] ?? '');
                        $ip    = (string)($r['ip'] ?? '');
                        $ref   = (string)($r['referencia'] ?? '');
                        $rol   = (string)($r['rol'] ?? 'guest');
                    ?>
                    <tr class="hover:bg-surface-container-low/60 transition-colors"
                        data-usuario="<?= $e(mb_strtolower($usr)) ?>"
                        data-correo="<?= $e(mb_strtolower($mail)) ?>"
                        data-ip="<?= $e($ip) ?>"
                        data-referencia="<?= $e(mb_strtolower($ref)) ?>"
                        data-rol="<?= $e($rol) ?>"
                        data-date="<?= $e($datePart) ?>"
                        data-date_sort="<?= $ts ?>">
                        <td class="px-4 py-3 tabular-nums whitespace-nowrap text-on-surface-muted">
                            <?= $e($fecha) ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($mail !== '' && $mail !== '—'): ?>
                                <p class="font-medium text-on-surface"><?= $e($usr) ?></p>
                                <p class="text-xs text-on-surface-subtle"><?= $e($mail) ?></p>
                            <?php else: ?>
                                <span class="text-on-surface-muted">Anónimo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $rl[1] ?>">
                                <?= $e($rl[0]) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-on-surface-muted hidden md:table-cell">
                            <?= $e($ip ?: '—') ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-on-surface-muted hidden lg:table-cell max-w-[220px] truncate" title="<?= $e($ref) ?>">
                            <?= $e($ref ?: '—') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-outline-variant/60 px-4 py-3 text-sm text-on-surface-muted sm:flex-row sm:items-center sm:justify-between">
            <p id="v-table-info">Mostrando 0–0 de 0 visitas</p>
            <div class="flex items-center gap-1">
                <button id="v-prev" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    <i class="bi bi-arrow-left text-[12px]"></i> Anterior
                </button>
                <span id="v-page-indicator" class="rounded-lg bg-primary px-2.5 py-1.5 text-xs font-semibold text-white">1</span>
                <button id="v-next" type="button" class="rounded-lg border border-outline-variant px-2.5 py-1.5 text-xs font-semibold inline-flex items-center gap-1">
                    Siguiente <i class="bi bi-arrow-right text-[12px]"></i>
                </button>
            </div>
        </div>
    </div>

    <p class="mt-3 text-xs text-on-surface-subtle">
        Se muestran hasta 10 000 registros en tabla. Para el total completo usa los botones de exportación.
    </p>

    </div><!-- /tab-registros -->

    <?php if (($auth_user['role'] ?? '') === 'admin'): ?>
    <!-- Modal limpiar visitas -->
    <div id="modal-purge" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" id="modal-purge-backdrop"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-outline-variant/60 px-6 py-4">
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                        <i class="bi bi-trash3 text-red-600"></i>
                    </span>
                    <h2 class="text-base font-semibold text-on-surface">Limpiar registro de visitas</h2>
                </div>
                <button type="button" id="modal-purge-close" class="rounded-lg p-1.5 text-on-surface-subtle hover:bg-surface-container-low">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/admin/reports/visits/purge" id="form-purge">
                <input type="hidden" name="csrf_token" value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                <div class="px-6 py-5 space-y-4">

                    <!-- Opciones -->
                    <p class="text-sm text-on-surface-muted">Selecciona qué registros deseas eliminar de forma permanente:</p>

                    <div class="space-y-3">
                        <!-- Todos -->
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-3 has-[:checked]:border-red-400 has-[:checked]:bg-red-50 transition-colors">
                            <input type="radio" name="mode" value="all" class="mt-0.5 accent-red-600" id="purge-all">
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Todos los registros</p>
                                <p class="text-xs text-on-surface-subtle">Vacía completamente la tabla de visitas.</p>
                            </div>
                        </label>

                        <!-- Anteriores a N días -->
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-3 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50 transition-colors">
                            <input type="radio" name="mode" value="older" class="mt-0.5 accent-amber-600" id="purge-older">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-on-surface">Anteriores a N días</p>
                                <p class="text-xs text-on-surface-subtle mb-2">Elimina visitas más antiguas que el número de días indicado.</p>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="days" id="purge-days" min="1" max="3650" value="30"
                                           class="w-24 rounded-lg border border-outline-variant px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none">
                                    <span class="text-xs text-on-surface-subtle">días atrás</span>
                                </div>
                            </div>
                        </label>

                        <!-- Rango de fechas -->
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-outline-variant p-3 has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50 transition-colors">
                            <input type="radio" name="mode" value="range" class="mt-0.5 accent-blue-600" id="purge-range">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-on-surface">Rango de fechas</p>
                                <p class="text-xs text-on-surface-subtle mb-2">Elimina visitas entre las fechas seleccionadas (inclusive).</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-on-surface-subtle">Desde</label>
                                        <input type="date" name="date_from" id="purge-from"
                                               class="mt-1 w-full rounded-lg border border-outline-variant px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="text-xs text-on-surface-subtle">Hasta</label>
                                        <input type="date" name="date_to" id="purge-to"
                                               class="mt-1 w-full rounded-lg border border-outline-variant px-2.5 py-1.5 text-sm focus:border-primary focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Advertencia -->
                    <div class="flex items-start gap-2 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                        <i class="bi bi-exclamation-triangle-fill text-red-500 mt-0.5 flex-shrink-0"></i>
                        <p class="text-xs text-red-700">Esta acción es <strong>irreversible</strong>. Los registros eliminados no se pueden recuperar.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-outline-variant/60 px-6 py-4">
                    <button type="button" id="modal-purge-cancel"
                            class="rounded-xl border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-purge-confirm"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <i class="bi bi-trash3"></i> Eliminar registros
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</section>

<script>
(() => {
    const tableBody   = document.getElementById('v-table-body');
    if (!tableBody) return;

    const rows        = Array.from(tableBody.querySelectorAll('tr'));
    const searchInput = document.getElementById('v-search');
    const rolSelect   = document.getElementById('v-rol');
    const dateInput   = document.getElementById('v-date');
    const pageSizeSel = document.getElementById('v-page-size');
    const prevBtn     = document.getElementById('v-prev');
    const nextBtn     = document.getElementById('v-next');
    const pageInd     = document.getElementById('v-page-indicator');
    const info        = document.getElementById('v-table-info');
    const sortBtns    = Array.from(document.querySelectorAll('.v-sort'));

    const state = {
        search: '', rol: 'all', date: '',
        page: 1, pageSize: 10,
        sortBy: 'date_sort', sortDir: 'desc',
    };

    const valueOf = (row, key) => {
        const v = row.dataset[key] ?? '';
        return key === 'date_sort' ? Number(v) : v;
    };

    const getFiltered = () => {
        const q = state.search.trim().toLowerCase();
        return rows.filter(row => {
            if (q) {
                const hay = [row.dataset.usuario, row.dataset.correo, row.dataset.ip, row.dataset.referencia].join(' ');
                if (!hay.includes(q)) return false;
            }
            if (state.rol !== 'all' && row.dataset.rol !== state.rol) return false;
            if (state.date && row.dataset.date !== state.date) return false;
            return true;
        });
    };

    const render = () => {
        const filtered = getFiltered().sort((a, b) => {
            const va = valueOf(a, state.sortBy);
            const vb = valueOf(b, state.sortBy);
            if (typeof va === 'number') return state.sortDir === 'asc' ? va - vb : vb - va;
            return state.sortDir === 'asc'
                ? String(va).localeCompare(String(vb), 'es')
                : String(vb).localeCompare(String(va), 'es');
        });

        const total      = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / state.pageSize));
        state.page       = Math.min(state.page, totalPages);
        const start      = (state.page - 1) * state.pageSize;
        const end        = Math.min(start + state.pageSize, total);

        rows.forEach(r => { r.style.display = 'none'; });
        filtered.slice(start, end).forEach(r => {
            r.style.display = '';
            tableBody.appendChild(r);
        });

        const from = total === 0 ? 0 : start + 1;
        const to   = total === 0 ? 0 : end;
        info.textContent = `Mostrando ${from}–${to} de ${total} visitas`;

        prevBtn.disabled = state.page <= 1;
        nextBtn.disabled = state.page >= totalPages;
        prevBtn.classList.toggle('opacity-50', prevBtn.disabled);
        prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);
        nextBtn.classList.toggle('opacity-50', nextBtn.disabled);
        nextBtn.classList.toggle('cursor-not-allowed', nextBtn.disabled);
        pageInd.textContent = `${state.page}/${totalPages}`;

        sortBtns.forEach(btn => {
            const icon = btn.querySelector('span');
            if (!icon) return;
            icon.textContent = btn.dataset.sort !== state.sortBy ? '⇅'
                : state.sortDir === 'asc' ? '↑' : '↓';
        });
    };

    searchInput?.addEventListener('input', e => { state.search = e.target.value; state.page = 1; render(); });
    rolSelect?.addEventListener('change',  e => { state.rol    = e.target.value; state.page = 1; render(); });
    dateInput?.addEventListener('change',  e => { state.date   = e.target.value; state.page = 1; render(); });
    pageSizeSel?.addEventListener('change',e => { state.pageSize = Number(e.target.value); state.page = 1; render(); });
    prevBtn?.addEventListener('click', () => { if (state.page > 1) { state.page--; render(); } });
    nextBtn?.addEventListener('click', () => { state.page++; render(); });

    sortBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const s = btn.dataset.sort;
            if (state.sortBy === s) {
                state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortBy = s;
                state.sortDir = s === 'date_sort' ? 'desc' : 'asc';
            }
            state.page = 1;
            render();
        });
    });

    render();
})();

// ── Tabs ─────────────────────────────────────────────────────────────────────
(() => {
    const tabs    = document.querySelectorAll('.v-tab');
    const panels  = { registros: document.getElementById('tab-registros'), kpis: document.getElementById('tab-kpis') };

    const activate = (name) => {
        tabs.forEach(t => {
            const active = t.dataset.tab === name;
            t.classList.toggle('bg-white',          active);
            t.classList.toggle('text-primary',       active);
            t.classList.toggle('shadow-sm',          active);
            t.classList.toggle('text-on-surface-subtle', !active);
            t.classList.toggle('hover:text-on-surface',  !active);
        });
        Object.entries(panels).forEach(([k, el]) => {
            if (el) el.classList.toggle('hidden', k !== name);
        });
        history.replaceState(null, '', location.pathname + (name !== 'registros' ? '?tab=' + name : ''));
    };

    tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab)));

    // Restaurar pestaña desde URL
    const urlTab = new URLSearchParams(location.search).get('tab');
    if (urlTab && panels[urlTab]) activate(urlTab);
})();

// ── Modal limpiar visitas ────────────────────────────────────────────────────
(() => {
    const modal    = document.getElementById('modal-purge');
    if (!modal) return;

    const openBtn  = document.getElementById('btn-purge-visits');
    const closeBtn = document.getElementById('modal-purge-close');
    const cancelBtn= document.getElementById('modal-purge-cancel');
    const backdrop = document.getElementById('modal-purge-backdrop');
    const confirm  = document.getElementById('btn-purge-confirm');
    const radios   = modal.querySelectorAll('input[type=radio]');
    const form     = document.getElementById('form-purge');

    const open  = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); };
    const close = () => { modal.classList.add('hidden');    modal.classList.remove('flex'); };

    openBtn?.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);

    // Habilitar botón confirmar solo cuando hay opción seleccionada
    radios.forEach(r => r.addEventListener('change', () => { confirm.disabled = false; }));

    // Confirmar antes de enviar
    form?.addEventListener('submit', e => {
        const mode = form.querySelector('input[name=mode]:checked')?.value;
        if (!mode) { e.preventDefault(); return; }

        const labels = { all: 'TODOS los registros de visitas', older: 'los registros anteriores al período indicado', range: 'los registros del rango de fechas seleccionado' };
        if (!confirm.window?.confirmed) {
            e.preventDefault();
            if (window.confirm(`¿Seguro que deseas eliminar ${labels[mode]}? Esta acción no se puede deshacer.`)) {
                form.submit();
            }
        }
    });
})();
</script>
