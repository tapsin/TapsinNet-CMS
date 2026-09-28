<?php declare(strict_types=1); ?>
<?php
/** PANEL ÖZETİ — sayımlar gerçek veritabanı sorgularından gelir. */
$modIcons = [
    'services' => '◆', 'projects' => '■', 'certificates' => '✦', 'gallery' => '▦',
    'videos' => '▶', 'news' => '✎', 'profiles' => '●', 'testimonials' => '”',
    'faq' => '?', 'social' => '◈', 'pages' => '¶',
];
$contentMods = array_filter(
    $modules,
    static fn (array $m): bool => ($m['group'] ?? '') === 'content' && isset($counts[$m['slug']])
);
?>
<div class="stat-grid">
    <div class="stat stat--accent">
        <span class="stat__value"><?= e(number_format((int) $badge['messages'], 0, ',', '.')) ?></span>
        <span class="stat__label"><?= e(t('admin.unread')) ?></span>
    </div>
    <div class="stat">
        <span class="stat__value"><?= e(number_format((int) $badge['comments'], 0, ',', '.')) ?></span>
        <span class="stat__label"><?= e(t('admin.pending')) ?></span>
    </div>
    <div class="stat">
        <span class="stat__value"><?= e(number_format((int) $totalViews, 0, ',', '.')) ?></span>
        <span class="stat__label"><?= e(t('admin.total_views')) ?></span>
    </div>
    <div class="stat">
        <span class="stat__value"><?= e(count(array_filter(\Core\ModuleRegistry::states()))) ?></span>
        <span class="stat__label"><?= e(t('admin.module_status')) ?></span>
    </div>
</div>

<div class="grid grid-2">
    <!-- İçerik durumu -->
    <section class="panel">
        <div class="panel__head">
            <h2 class="panel__title"><?= e(t('admin.overview')) ?></h2>
            <a href="<?= e(url('/admin/moduller')) ?>" class="btn btn--ghost btn--sm"><?= e(t('admin.modules')) ?></a>
        </div>
        <div class="panel__body">
            <div class="module-grid">
                <?php foreach ($contentMods as $m): ?>
                    <a href="<?= e(url('/admin/' . $m['slug'])) ?>" class="module-card <?= $m['is_active'] ? '' : 'is-off' ?>">
                        <span class="module-card__head">
                            <span class="module-card__glyph" aria-hidden="true"><?= e($modIcons[$m['slug']] ?? '●') ?></span>
                            <span class="module-card__name"><?= e(\Core\ModuleRegistry::name($m['slug'])) ?></span>
                        </span>
                        <span class="module-card__foot">
                            <strong><?= e(number_format((int) $counts[$m['slug']], 0, ',', '.')) ?></strong>
                            <span class="badge <?= $m['is_active'] ? 'badge--success' : 'badge--muted' ?>">
                                <?= e($m['is_active'] ? t('admin.active') : t('admin.passive')) ?>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Son mesajlar -->
    <section class="panel">
        <div class="panel__head">
            <h2 class="panel__title"><?= e(t('admin.messages')) ?></h2>
            <a href="<?= e(url('/admin/mesajlar')) ?>" class="btn btn--ghost btn--sm"><?= e(t('home.view_all')) ?></a>
        </div>
        <div class="panel__body">
            <?php if ($recentMsg === []): ?>
                <p class="u-faint"><?= e(t('admin.no_messages')) ?></p>
            <?php else: ?>
                <div class="inbox-list">
                    <?php foreach ($recentMsg as $m): ?>
                        <a href="<?= e(url('/admin/mesajlar/' . (int) $m['id'])) ?>"
                           class="inbox-item <?= (int) $m['is_read'] === 0 ? 'is-unread' : '' ?>">
                            <span>
                                <span class="inbox-item__from"><?= e($m['name']) ?></span>
                                <span class="inbox-item__subject"><?= e(str_limit((string) ($m['subject'] ?: $m['message']), 64)) ?></span>
                            </span>
                            <span class="inbox-item__meta"><?= e(time_ago((string) $m['created_at'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Son haberler -->
<section class="panel">
    <div class="panel__head">
        <h2 class="panel__title"><?= e(t('admin.recent_activity')) ?></h2>
        <a href="<?= e(url('/admin/news')) ?>" class="btn btn--ghost btn--sm"><?= e(t('admin.news')) ?></a>
    </div>
    <div class="panel__body">
        <?php if ($recentNews === []): ?>
            <p class="u-faint"><?= e(t('admin.no_activity')) ?></p>
        <?php else: ?>
            <div class="inbox-list">
                <?php foreach ($recentNews as $n): ?>
                    <a href="<?= e(url('/admin/news/duzenle/' . (int) $n['id'])) ?>" class="inbox-item">
                        <span>
                            <span class="inbox-item__from"><?= e(str_limit((string) $n['title_tr'], 56)) ?></span>
                            <span class="inbox-item__preview"><?= e(str_limit((string) ($n['summary_tr'] ?: ''), 80)) ?></span>
                        </span>
                        <span class="inbox-item__meta"><?= e(time_ago((string) $n['created_at'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<p class="u-faint u-mono">
    TapsinNet <?= e($siteVersion) ?> · PHP <?= e($phpVersion) ?>
</p>
