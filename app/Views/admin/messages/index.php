<?php declare(strict_types=1); ?>
<?php /** GELEN KUTUSU — okunmamış/yıldızlı/arşiv filtreleri + arama. */ ?>
<div class="stat-grid">
    <div class="stat"><span class="stat__value"><?= e(number_format((int) $counts['all'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.total')) ?></span></div>
    <div class="stat stat--accent"><span class="stat__value"><?= e(number_format((int) $counts['unread'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.unread')) ?></span></div>
    <div class="stat"><span class="stat__value"><?= e(number_format((int) $counts['starred'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.star')) ?></span></div>
    <div class="stat"><span class="stat__value"><?= e(number_format((int) $counts['archived'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.archive')) ?></span></div>
</div>

<div class="toolbar">
    <div class="toolbar__group">
        <?php foreach (['' => t('admin.stats.total'), 'unread' => t('admin.unread'), 'starred' => t('admin.star'), 'archived' => t('admin.archive')] as $key => $label): ?>
            <a href="<?= e(url('/admin/mesajlar', $key === '' ? [] : ['filter' => $key])) ?>"
               class="filter-chip <?= $filter === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
    <form class="toolbar__group toolbar__grow" method="get" action="<?= e(url('/admin/mesajlar')) ?>" role="search">
        <?php if ($filter !== ''): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <label class="visually-hidden" for="q"><?= e(t('admin.search_ph')) ?></label>
        <input type="search" id="q" name="q" class="input" value="<?= e($term) ?>"
               placeholder="<?= e(t('admin.search_ph')) ?>" data-search autocomplete="off">
        <button type="submit" class="admin-search-submit" aria-label="<?= e(t('search.submit')) ?>" title="<?= e(t('search.submit')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4.5 4.5"></path></svg>
        </button>
    </form>
    <?php if ($counts['unread'] > 0): ?>
        <form method="post" action="<?= e(url('/admin/mesajlar/tumunu-okundu')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('admin.mark_all_read')) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <div class="empty-state__glyph" aria-hidden="true">✉</div>
        <h2 class="empty-state__title"><?= e(t('admin.no_messages')) ?></h2>
    </div>
<?php else: ?>
    <div class="inbox-list">
        <?php foreach ($rows as $m): $id = (int) $m['id']; ?>
            <div class="inbox-item <?= (int) $m['is_read'] === 0 ? 'is-unread' : '' ?> <?= (int) $m['is_archived'] === 1 ? 'u-faint' : '' ?>">
                <span>
                    <a href="<?= e(url('/admin/mesajlar/' . $id)) ?>">
                        <span class="inbox-item__from"><?= e($m['name']) ?></span>
                        <span class="inbox-item__subject"><?= e($m['subject'] ?: str_limit((string) $m['message'], 70)) ?></span>
                        <span class="inbox-item__preview"><?= e($m['email']) ?></span>
                    </a>
                </span>
                <span class="inbox-item__meta">
                    <?php if ((int) $m['is_starred'] === 1): ?><span title="<?= e(t('admin.star')) ?>">★</span><?php endif; ?>
                    <time datetime="<?= e((string) $m['created_at']) ?>"><?= e(time_ago((string) $m['created_at'])) ?></time>
                    <form method="post" action="<?= e(url('/admin/mesajlar/islem/' . $id)) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" name="islem" value="<?= (int) $m['is_archived'] === 1 ? 'arsivden-cikar' : 'arsiv' ?>"
                                class="btn btn--ghost btn--sm"><?= (int) $m['is_archived'] === 1 ? t('admin.unarchive') : t('admin.archive') ?></button>
                    </form>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $paginator->links('admin.partials.pagination') ?>
