<?php declare(strict_types=1); ?>
<header class="admin__topbar">
    <button type="button" class="btn btn--ghost btn--icon admin__sidebar-toggle"
            data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="false" aria-label="<?= e(t('nav.menu')) ?>">
        ☰
    </button>

    <h1><?= e($title ?? t('admin.dashboard')) ?></h1>

    <form class="admin__search" method="get" action="<?= e(url('/admin/ara')) ?>" role="search">
        <label class="visually-hidden" for="adminSearch"><?= e(t('admin.quick_search')) ?></label>
        <input type="search" id="adminSearch" name="q" class="input" data-search
               value="<?= e((string) ($term ?? '')) ?>"
               placeholder="<?= e(t('admin.search_ph')) ?>" autocomplete="off">
        <button type="submit" class="admin__search-btn" aria-label="<?= e(t('search.submit')) ?>">⌕</button>
    </form>

    <?php if (!empty($authUser)): ?>
        <div class="admin__user">
            <span>
                <?= e($authUser['name']) ?><br>
                <span class="u-faint"><?= e($authUser['email']) ?></span>
            </span>
            <form method="post" action="<?= e(url('/admin/cikis')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('admin.logout')) ?></button>
            </form>
        </div>
    <?php endif; ?>
</header>
