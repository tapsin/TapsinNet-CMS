<?php declare(strict_types=1); ?>
<?php if (!$paginator->hasPages()): ?>
    <?php return; ?>
<?php endif; ?>
<nav class="pagination" aria-label="<?= t('pagination.label') ?>">
    <ul class="pagination__list">
        <?php if ($paginator->currentPage > 1): ?>
            <li>
                <a href="<?= e($paginator->url($paginator->currentPage - 1)) ?>"
                   class="pagination__link"
                   aria-label="<?= t('pagination.prev') ?>">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
            </li>
        <?php else: ?>
            <li>
                <span class="pagination__link pagination__link--disabled" aria-hidden="true">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </span>
            </li>
        <?php endif; ?>

        <?php foreach ($paginator->window(2) as $page): ?>
            <?php if ($page === '…'): ?>
                <li><span class="pagination__gap" aria-hidden="true">…</span></li>
            <?php else: ?>
                <?php $isCurrent = $page === $paginator->currentPage; ?>
                <li>
                    <?php if ($isCurrent): ?>
                        <span class="pagination__link is-current"
                              aria-current="page"
                              aria-label="<?= t('pagination.current', ['page' => $page]) ?>">
                            <?= e((string) $page) ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= e($paginator->url($page)) ?>"
                           class="pagination__link"
                           aria-label="<?= t('pagination.page', ['page' => $page]) ?>">
                            <?= e((string) $page) ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($paginator->currentPage < $paginator->lastPage): ?>
            <li>
                <a href="<?= e($paginator->url($paginator->currentPage + 1)) ?>"
                   class="pagination__link"
                   aria-label="<?= t('pagination.next') ?>">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </li>
        <?php else: ?>
            <li>
                <span class="pagination__link pagination__link--disabled" aria-hidden="true">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </span>
            </li>
        <?php endif; ?>
    </ul>
</nav>