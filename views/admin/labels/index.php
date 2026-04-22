<?php
use Helpers\Icons;
use Helpers\Isbn;

$e = fn(mixed $v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$page = max(1, (int) ($page ?? 1));
$perPage = (int) ($per_page ?? 20);
$lastPage = max(1, (int) ($last_page ?? 1));
$total = max(0, (int) ($total ?? 0));
$from = max(0, (int) ($from ?? 0));
$to = max(0, (int) ($to ?? 0));

$actionIconClass = 'inline-flex h-8 w-8 items-center justify-center rounded-lg border border-outline-variant text-on-surface-muted transition-colors hover:bg-surface-container-low hover:text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30';
$actionIconDisabledClass = 'inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg border border-outline-variant bg-surface-container-low text-on-surface-subtle opacity-60';
?>

<section class="p-6 lg:p-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="label-sm">Admin · Inventario</p>
            <h1 class="headline-lg text-on-surface">Etiquetas de recursos</h1>
            <p class="mt-1 text-sm text-on-surface-muted">Listado tipo CRUD con busqueda, paginacion y vista previa de etiqueta.</p>
        </div>
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors print:hidden">
            <?= Icons::print('w-4 h-4') ?> Imprimir pagina
        </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/labels" class="mb-4 grid gap-3 rounded-2xl border border-outline-variant/60 bg-white p-4 shadow-ambient print:hidden md:grid-cols-[1fr_auto_auto]">
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-on-surface-subtle">
                <?= Icons::search('w-4 h-4') ?>
            </span>
            <input type="search"
                   id="labelsQuickSearch"
                   name="q"
                   value="<?= $e($search) ?>"
                   placeholder="Buscar por titulo, ISBN o codigo de clasificacion"
                   class="w-full rounded-xl border border-outline-variant bg-white py-2.5 pl-9 pr-4 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>

        <select name="per_page" disabled
                class="rounded-xl border border-outline-variant bg-white px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
            <option value="5" selected>5 por pagina</option>
        </select>

        <div class="flex gap-2">
            <input type="hidden" name="page" value="1">
            <button type="submit"
                    class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 transition-opacity">
                Buscar
            </button>
            <?php if ($search !== ''): ?>
                <button type="submit" name="reset" value="1"
                   class="rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-sm text-on-surface-muted hover:bg-surface-container-low transition-colors">
                    Limpiar
                </button>
            <?php endif; ?>
        </div>
    </form>

    <div class="mb-3 flex items-center justify-between text-sm text-on-surface-muted print:hidden">
        <p>
            Mostrando <strong><?= $from ?></strong>-<strong><?= $to ?></strong> de <strong><?= $total ?></strong>
            <?= $search !== '' ? ' resultados para "<strong>' . $e($search) . '</strong>"' : ' registros' ?>.
        </p>
        <p>Pagina <?= $page ?> de <?= $lastPage ?> · <span id="labelsVisibleCount"><?= count($resources) ?></span> visibles</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-ambient">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-outline-variant/60">
                <thead class="bg-surface-container-low">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-on-surface-muted">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-on-surface-muted">Recurso</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-on-surface-muted">ISBN</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-on-surface-muted">Clasificacion</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-on-surface-muted">Acciones</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                <?php if (empty($resources)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-on-surface-subtle">
                            <?= $search !== '' ? 'No hay resultados para tu busqueda.' : 'No hay recursos fisicos activos en catalogo.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resources as $r): ?>
                        <?php
                        $authors = trim((string) ($r['authors'] ?? ''));
                        if ($authors !== '' && str_starts_with($authors, '[')) {
                            $decodedAuthors = json_decode($authors, true);
                            if (is_array($decodedAuthors)) {
                                $authors = implode(', ', array_map(static fn(mixed $v): string => trim((string) $v), $decodedAuthors));
                            }
                        }

                        $rawIsbn = preg_replace('/[^0-9X]/i', '', strtoupper((string) ($r['isbn'] ?? '')));
                        $isbn13 = strlen((string) $rawIsbn) === 13 && ctype_digit((string) $rawIsbn)
                            ? (string) $rawIsbn
                            : null;

                        if ($isbn13 === null && $rawIsbn !== null && $rawIsbn !== '') {
                            $isbn13 = Isbn::normalize((string) $rawIsbn);
                        }

                        $barcodeUrl = $isbn13 !== null ? (BASE_URL . '/admin/barcode/' . rawurlencode($isbn13)) : '';
                        $qrUrl = BASE_URL . '/admin/qr/resource/' . (int) $r['id'];
                        ?>
                        <tr class="hover:bg-surface-container-low/40"
                            data-filter="<?= $e(mb_strtolower(trim(((string) ($r['title'] ?? '')) . ' ' . $authors . ' ' . ((string) ($isbn13 ?? '')) . ' ' . ((string) ($r['classification_code'] ?? '')) . ' #' . ((string) ((int) $r['id']))), 'UTF-8')) ?>">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-on-surface-muted">#<?= (int) $r['id'] ?></td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-on-surface"><?= $e($r['title']) ?></p>
                                <?php if ($authors !== ''): ?>
                                    <p class="mt-0.5 text-xs text-on-surface-muted"><?= $e($authors) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-on-surface-muted"><?= $isbn13 !== null ? $e($isbn13) : 'Sin ISBN' ?></td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-on-surface-muted"><?= $e($r['classification_code'] ?: 'N/D') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <button type="button"
                                            class="<?= $actionIconClass ?>"
                                            data-tooltip="Vista previa de etiqueta"
                                            data-tooltip-placement="top"
                                            data-label-preview="1"
                                            data-id="<?= (int) $r['id'] ?>"
                                            data-title="<?= $e($r['title']) ?>"
                                            data-authors="<?= $e($authors) ?>"
                                            data-isbn="<?= $e($isbn13 ?? '') ?>"
                                            data-classification="<?= $e((string) ($r['classification_code'] ?? '')) ?>"
                                            data-barcode-url="<?= $e($barcodeUrl) ?>"
                                            data-qr-url="<?= $e($qrUrl) ?>">
                                        <?= Icons::eye('w-4 h-4') ?>
                                    </button>

                                    <?php if ($isbn13 !== null): ?>
                                        <a href="<?= $e($barcodeUrl) ?>"
                                           download="barcode_<?= $e($isbn13) ?>.png"
                                           class="<?= $actionIconClass ?>"
                                           data-tooltip="Descargar codigo EAN-13"
                                           data-tooltip-placement="top">
                                            <?= Icons::download('w-4 h-4') ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="<?= $actionIconDisabledClass ?>"
                                              data-tooltip="EAN-13 no disponible (recurso sin ISBN valido)"
                                              data-tooltip-placement="top"
                                              aria-disabled="true">
                                            <?= Icons::download('w-4 h-4') ?>
                                        </span>
                                    <?php endif; ?>

                                    <a href="<?= $e($qrUrl) ?>"
                                       download="qr_resource_<?= (int) $r['id'] ?>.png"
                                       class="<?= $actionIconClass ?>"
                                       data-tooltip="Descargar codigo interno"
                                       data-tooltip-placement="top">
                                        <?= Icons::download('w-4 h-4') ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr id="labelsNoMatchRow" class="hidden">
                    <td colspan="5" class="px-4 py-10 text-center text-sm text-on-surface-subtle">
                        No hay coincidencias en esta pagina.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($lastPage > 1): ?>
        <nav class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between print:hidden" aria-label="Paginacion">
            <p class="text-sm text-on-surface-muted">Pagina <?= $page ?> de <?= $lastPage ?></p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="<?= BASE_URL ?>/admin/labels" class="inline">
                    <input type="hidden" name="q" value="<?= $e($search) ?>">
                    <input type="hidden" name="page" value="<?= max(1, $page - 1) ?>">
                    <button type="submit"
                   class="rounded-lg border border-outline-variant px-3 py-2 text-sm <?= $page > 1 ? 'hover:bg-surface-container-low text-on-surface-muted' : 'pointer-events-none opacity-50 text-on-surface-subtle' ?>"
                   <?= $page > 1 ? '' : 'disabled' ?>>
                    Anterior
                    </button>
                </form>

                <?php
                $start = max(1, $page - 2);
                $end = min($lastPage, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <form method="POST" action="<?= BASE_URL ?>/admin/labels" class="inline">
                        <input type="hidden" name="q" value="<?= $e($search) ?>">
                        <input type="hidden" name="page" value="<?= $i ?>">
                        <button type="submit"
                       class="rounded-lg border px-3 py-2 text-sm <?= $i === $page ? 'border-primary bg-primary text-white' : 'border-outline-variant text-on-surface-muted hover:bg-surface-container-low' ?>">
                        <?= $i ?>
                        </button>
                    </form>
                <?php endfor; ?>

                <form method="POST" action="<?= BASE_URL ?>/admin/labels" class="inline">
                    <input type="hidden" name="q" value="<?= $e($search) ?>">
                    <input type="hidden" name="page" value="<?= min($lastPage, $page + 1) ?>">
                    <button type="submit"
                   class="rounded-lg border border-outline-variant px-3 py-2 text-sm <?= $page < $lastPage ? 'hover:bg-surface-container-low text-on-surface-muted' : 'pointer-events-none opacity-50 text-on-surface-subtle' ?>"
                   <?= $page < $lastPage ? '' : 'disabled' ?>>
                    Siguiente
                    </button>
                </form>
            </div>
        </nav>
    <?php endif; ?>
</section>

<div id="labelPreviewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 print:hidden" role="dialog" aria-modal="true" aria-labelledby="labelPreviewTitle">
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-ambient">
        <div class="flex items-center justify-between border-b border-outline-variant/60 px-5 py-3">
            <h2 id="labelPreviewTitle" class="text-base font-semibold text-on-surface">Vista previa de etiqueta</h2>
            <button type="button" id="closeLabelPreview" class="rounded-lg border border-outline-variant px-2 py-1 text-sm text-on-surface-muted hover:bg-surface-container-low">Cerrar</button>
        </div>

        <div class="space-y-3 p-5">
            <div>
                <p id="previewTitle" class="text-sm font-semibold text-on-surface"></p>
                <p id="previewAuthors" class="text-xs text-on-surface-muted"></p>
                <p id="previewMeta" class="mt-1 text-xs text-on-surface-subtle"></p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <article class="rounded-xl border border-outline-variant/60 bg-surface-container-low p-3">
                    <p class="mb-1 text-xs font-medium text-on-surface-muted">EAN-13</p>
                    <img id="previewBarcode" src="" alt="Barcode" class="h-auto max-w-full rounded bg-white p-1">
                    <p id="previewNoIsbn" class="hidden text-xs italic text-on-surface-subtle">Este recurso no tiene ISBN valido para EAN-13.</p>
                </article>

                <article class="rounded-xl border border-outline-variant/60 bg-surface-container-low p-3">
                    <p class="mb-1 text-xs font-medium text-on-surface-muted">Codigo interno</p>
                    <img id="previewQr" src="" alt="Codigo interno" class="h-auto max-w-full rounded bg-white p-1">
                </article>
            </div>

            <div class="flex flex-wrap gap-2">
                <a id="previewDownloadEan" href="#" download class="inline-flex items-center gap-1 rounded-lg border border-outline-variant px-3 py-1.5 text-xs text-on-surface-muted hover:bg-surface-container-low">
                    <?= Icons::download('w-3.5 h-3.5') ?> Descargar EAN-13
                </a>
                <a id="previewDownloadQr" href="#" download class="inline-flex items-center gap-1 rounded-lg border border-outline-variant px-3 py-1.5 text-xs text-on-surface-muted hover:bg-surface-container-low">
                    <?= Icons::download('w-3.5 h-3.5') ?> Descargar codigo ID
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('labelPreviewModal');
    const closeBtn = document.getElementById('closeLabelPreview');

    if (!modal || !closeBtn) {
        return;
    }

    const previewTitle = document.getElementById('previewTitle');
    const previewAuthors = document.getElementById('previewAuthors');
    const previewMeta = document.getElementById('previewMeta');
    const previewBarcode = document.getElementById('previewBarcode');
    const previewQr = document.getElementById('previewQr');
    const previewNoIsbn = document.getElementById('previewNoIsbn');
    const previewDownloadEan = document.getElementById('previewDownloadEan');
    const previewDownloadQr = document.getElementById('previewDownloadQr');
    const quickSearchInput = document.getElementById('labelsQuickSearch');
    const tableRows = Array.from(document.querySelectorAll('tbody tr[data-filter]'));
    const noMatchRow = document.getElementById('labelsNoMatchRow');
    const visibleCountEl = document.getElementById('labelsVisibleCount');

    const normalizeText = (value) =>
        (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');

    const applyTableFilter = () => {
        if (!quickSearchInput || tableRows.length === 0) {
            return;
        }

        const term = normalizeText(quickSearchInput.value.trim());
        let visible = 0;

        tableRows.forEach((row) => {
            const haystack = normalizeText(row.getAttribute('data-filter') || '');
            const match = term === '' || haystack.includes(term);
            row.classList.toggle('hidden', !match);
            if (match) visible++;
        });

        if (noMatchRow) {
            noMatchRow.classList.toggle('hidden', visible > 0);
        }
        if (visibleCountEl) {
            visibleCountEl.textContent = String(visible);
        }
    };

    const openModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    document.querySelectorAll('[data-label-preview="1"]').forEach((button) => {
        button.addEventListener('click', () => {
            const title = button.getAttribute('data-title') || '';
            const authors = button.getAttribute('data-authors') || '';
            const isbn = button.getAttribute('data-isbn') || '';
            const classification = button.getAttribute('data-classification') || '';
            const barcodeUrl = button.getAttribute('data-barcode-url') || '';
            const qrUrl = button.getAttribute('data-qr-url') || '';
            const id = button.getAttribute('data-id') || '';

            previewTitle.textContent = title;
            previewAuthors.textContent = authors || 'Autor no registrado';
            previewMeta.textContent = `ID: #${id} · ISBN: ${isbn || 'N/D'} · Clasificacion: ${classification || 'N/D'}`;

            previewQr.src = qrUrl;
            previewDownloadQr.href = qrUrl;
            previewDownloadQr.download = `qr_resource_${id}.png`;

            if (isbn && barcodeUrl) {
                previewBarcode.src = barcodeUrl;
                previewBarcode.classList.remove('hidden');
                previewNoIsbn.classList.add('hidden');
                previewDownloadEan.href = barcodeUrl;
                previewDownloadEan.download = `barcode_${isbn}.png`;
                previewDownloadEan.classList.remove('hidden');
            } else {
                previewBarcode.removeAttribute('src');
                previewBarcode.classList.add('hidden');
                previewNoIsbn.classList.remove('hidden');
                previewDownloadEan.classList.add('hidden');
            }

            openModal();
        });
    });

    closeBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    if (quickSearchInput) {
        quickSearchInput.addEventListener('input', applyTableFilter);
        applyTableFilter();
    }
})();
</script>

<style media="print">
    nav, header, aside, footer,
    .print\:hidden,
    #labelPreviewModal { display: none !important; }

    body { background: white !important; }
</style>
