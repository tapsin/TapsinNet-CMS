<?php declare(strict_types=1); ?>
<?php
/**
 * Yönetim kenar çubuğu. Menü, modül kataloğundan üretilir — pasif modüller
 * listede kalır ama "pasif" rozetiyle gösterilir (kullanıcı nereyi açtığını
 * bilsin). Sistem modülleri (mesajlar, yorumlar) kapatılamaz.
 */
$badge = $badge ?? ['messages' => 0, 'comments' => 0];
$current = $currentRoute ?? '';
$mods   = \Models\Module::catalog();

$groupLabels = [];
foreach (['content', 'social', 'system'] as $g) {
    $k    = 'admin.modules.' . $g;
    $val  = t($k);
    $groupLabels[$g] = $val === $k ? ucfirst($g) : $val;
}
$systemSlugs = ['messages', 'comments', 'search'];
?>
<aside class="admin__sidebar" id="adminSidebar">
    <a href="<?= e(url('/admin')) ?>" class="admin__brand">
        <span class="admin__brand-name"><?= e($siteName ?? 'TapsinNet') ?></span>
        <span class="admin__brand-tag"><?= e(t('admin.dashboard')) ?></span>
    </a>

    <nav class="admin__nav" aria-label="<?= e(t('admin.modules')) ?>">
        <a href="<?= e(url('/admin')) ?>" class="admin__nav-link <?= $current === 'admin.dashboard' ? 'is-active' : '' ?>">
            <span class="admin__nav-glyph" aria-hidden="true">▦</span>
            <?= e(t('admin.dashboard')) ?>
        </a>

        <?php foreach (['content' => '◆', 'social' => '◈', 'system' => '◇'] as $groupKey => $groupGlyph): ?>
            <?php
            $groupMods = array_values(array_filter(
                $mods,
                static fn (array $m): bool => ($m['group'] ?? 'content') === $groupKey
            ));
            if ($groupMods === []) { continue; }
            ?>
            <p class="admin__nav-group"><?= e($groupLabels[$groupKey] ?? $groupKey) ?></p>

            <?php foreach ($groupMods as $m):
                $slug = $m['slug'];
                // 'admin.services.index' → /admin/services
                $routeName = preg_replace('/\.index$/', '', (string) $m['admin']);
                $href = $m['admin'] ? url('/' . str_replace('.', '/', $routeName)) : null;
                if ($href === null) { continue; }
                $isActive = $current === $m['admin'];
                ?>
                <a href="<?= e($href) ?>" class="admin__nav-link <?= $isActive ? 'is-active' : '' ?>"
                   <?= $m['is_active'] ? '' : 'title="' . e(t('admin.passive')) . '"' ?>>
                    <?php
                    // Kısa etiket çevirisi yoksa modülün kendi adına düş.
                    // DİKKAT: Translator::t()'nin 3. parametresi $lang'dir —
                    // fallback olarak 3. argüman geçirilirse çeviri
                    // bulunamaz ve anahtarın kendisi ekrana basılır
                    // ("admin.module_short.services"). Önce varlığı sorulur.
                    $shortKey = 'admin.module_short.' . $slug;
                    $short    = t($shortKey);
                    $short    = $short === $shortKey ? \Core\ModuleRegistry::name($slug) : $short;
                    ?>
                    <span class="admin__nav-glyph" aria-hidden="true"><?= e($m['icon_glyph']) ?></span>
                    <?= e($short) ?>
                    <?php if ($slug === 'messages' && $badge['messages'] > 0): ?>
                        <span class="pill-count"><?= e(number_format($badge['messages'], 0, ',', '.')) ?></span>
                    <?php elseif ($slug === 'comments' && $badge['comments'] > 0): ?>
                        <span class="pill-count"><?= e(number_format($badge['comments'], 0, ',', '.')) ?></span>
                    <?php elseif (!$m['is_active']): ?>
                        <span class="pill-count pill-count--muted"><?= e(t('admin.passive')) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <p class="admin__nav-group"><?= e(t('admin.settings')) ?></p>
        <a href="<?= e(url('/admin/ayarlar')) ?>" class="admin__nav-link <?= $current === 'admin.settings.index' ? 'is-active' : '' ?>">
            <span class="admin__nav-glyph" aria-hidden="true">⚙</span><?= e(t('admin.settings')) ?>
        </a>
        <a href="<?= e(url('/admin/hesap')) ?>" class="admin__nav-link <?= $current === 'admin.user.edit' ? 'is-active' : '' ?>">
            <span class="admin__nav-glyph" aria-hidden="true">◎</span><?= e(t('admin.account')) ?>
        </a>
    </nav>

    <div class="admin__side-foot">
        <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= e(t('admin.view_site')) ?> ↗</a>
    </div>
</aside>
