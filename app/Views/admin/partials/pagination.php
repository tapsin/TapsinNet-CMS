<?php declare(strict_types=1); ?>
<?php
/** @var \Core\Paginator $paginator */
if ($paginator === null || !$paginator->hasPages()) { return; }
$window = $paginator->window(1);
?>
<nav class="pagination" aria-label="<?= e(t('pagination.label')) ?>">
    <?php if ($paginator->currentPage > 1): ?>
        <a href="<?= e($paginator->url($paginator->currentPage - 1)) ?>" class="pagination__link" rel="prev">←</a>
    <?php else: ?>
        <span class="pagination__link" aria-disabled="true">←</span>
    <?php endif; ?>

    <?php foreach ($window as $p): ?>
        <?php if ($p === '…'): ?>
            <span class="pagination__gap" aria-hidden="true">…</span>
        <?php elseif ($p === $paginator->currentPage): ?>
            <span class="pagination__link is-current" aria-current="page"><?= e($p) ?></span>
        <?php else: ?>
            <a href="<?= e($paginator->url((int) $p)) ?>" class="pagination__link"><?= e($p) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($paginator->currentPage < $paginator->lastPage): ?>
        <a href="<?= e($paginator->url($paginator->currentPage + 1)) ?>" class="pagination__link" rel="next">→</a>
    <?php else: ?>
        <span class="pagination__link" aria-disabled="true">→</span>
    <?php endif; ?>

    <p class="pagination__meta">
        <?= e(t('admin.showing', [
            'from'  => $paginator->firstItem(),
            'to'    => $paginator->lastItem(),
            'total' => number_format($paginator->total, 0, ',', '.'),
        ])) ?>
    </p>
</nav>
