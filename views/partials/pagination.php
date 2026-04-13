<?php
/**
 * Paginación reutilizable
 *
 * Variables esperadas:
 *   $currentPage  — Página actual (int)
 *   $totalPages   — Total de páginas (int)
 *   $baseUrl      — URL base sin parámetro de página (string)
 */

if ($totalPages <= 1) return;

$range = 2;
$start = max(1, $currentPage - $range);
$end   = min($totalPages, $currentPage + $range);
?>
<nav class="flex items-center justify-center space-x-1 mt-6" aria-label="Paginación">
    <?php if ($currentPage > 1): ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=' . ($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>"
           class="px-3 py-2 text-sm text-gray-600 bg-white border rounded hover:bg-gray-50"
           aria-label="Anterior">&laquo;</a>
    <?php endif; ?>

    <?php if ($start > 1): ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=1', ENT_QUOTES, 'UTF-8') ?>"
           class="px-3 py-2 text-sm text-gray-600 bg-white border rounded hover:bg-gray-50">1</a>
        <?php if ($start > 2): ?>
            <span class="px-2 py-2 text-sm text-gray-400">&hellip;</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $currentPage): ?>
            <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= htmlspecialchars($baseUrl . '?page=' . $i, ENT_QUOTES, 'UTF-8') ?>"
               class="px-3 py-2 text-sm text-gray-600 bg-white border rounded hover:bg-gray-50"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?>
            <span class="px-2 py-2 text-sm text-gray-400">&hellip;</span>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=' . $totalPages, ENT_QUOTES, 'UTF-8') ?>"
           class="px-3 py-2 text-sm text-gray-600 bg-white border rounded hover:bg-gray-50"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="<?= htmlspecialchars($baseUrl . '?page=' . ($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>"
           class="px-3 py-2 text-sm text-gray-600 bg-white border rounded hover:bg-gray-50"
           aria-label="Siguiente">&raquo;</a>
    <?php endif; ?>
</nav>
