<?php declare(strict_types=1); ?>
<?php
/**
 * Menü kalemi sayısı 11'e çıkınca header tek satıra sığmıyor ve linkler
 * ikinci satıra sarıyor. İlk 5 modül doğrudan, kalanı "Daha fazla"
 * açılır menüsünde gösterilir. Mobilde hepsi sıralı listelenir.
 */
$navModules  = is_array($navModules) ? array_values($navModules) : [];
$navPrimary  = array_slice($navModules, 0, 5);
$navOverflow = array_slice($navModules, 5);
$isActiveNav = static function (string $slug) use ($currentRoute): bool {
    return $currentRoute === $slug || str_starts_with($currentRoute, $slug . '.');
};
?>
<header class="masthead" role="banner">
    <div class="masthead__inner wrap">
        <div class="masthead__brand">
            <?php $siteLogo = upload_url(setting('logo_image', '')); ?>
            <a href="<?= url('/') ?>" class="masthead__logo" aria-label="<?= e($siteName) ?> — <?= t('nav.home') ?>">
                <?php if ($siteLogo): ?><img src="<?= e($siteLogo) ?>" alt="" loading="eager">
                <?php else: ?><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><polygon points="12,2 22,22 2,22" fill="var(--color-electric-iris)"/></svg><?php endif; ?>
            </a>
        </div>

        <nav class="masthead__nav" role="navigation" aria-label="<?= t('nav.menu') ?>">
            <ul class="masthead__links">
                <?php foreach ($navPrimary as $slug): ?>
                    <?php
                        $name = module($slug, 'name');
                        $url  = module_url($slug);
                        $active = $isActiveNav($slug);
                    ?>
                    <?php if ($name && $url): ?>
                        <li>
                            <a href="<?= e($url) ?>" class="nav-link<?= $active ? ' is-active' : '' ?>" aria-current="<?= $active ? 'page' : 'false' ?>">
                                <?= e($name) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if ($navOverflow !== []): ?>
                    <li class="masthead__more">
                        <details class="dropdown">
                            <summary class="nav-link dropdown__trigger<?= count(array_filter($navOverflow, static fn ($s) => $isActiveNav($s))) ? ' is-active' : '' ?>">
                                <span><?= t('nav.more') ?></span>
                                <svg class="dropdown__caret" aria-hidden="true" width="10" height="6" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 1l4 4 4-4"/></svg>
                            </summary>
                            <ul class="dropdown__menu">
                                <?php foreach ($navOverflow as $slug): ?>
                                    <?php
                                        $name = module($slug, 'name');
                                        $url  = module_url($slug);
                                        $active = $isActiveNav($slug);
                                    ?>
                                    <?php if ($name && $url): ?>
                                        <li>
                                            <a href="<?= e($url) ?>" class="nav-link<?= $active ? ' is-active' : '' ?>" aria-current="<?= $active ? 'page' : 'false' ?>">
                                                <?= e($name) ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="masthead__actions">
            <a href="<?= e(url('/iletisim')) ?>" class="btn btn--primary btn--lg"><?= t('nav.contact') ?></a>

            <div class="masthead__lang lang-switch" role="group" aria-label="<?= t('nav.language') ?>">
                <?php $langs = ['tr' => 'Türkçe', 'en' => 'English']; ?>
                <?php foreach ($langs as $code => $label): ?>
                    <?php $isCurrent = $locale === $code; ?>
                    <a href="<?= e(url(($code === 'tr' ? '' : $code . '/') . ltrim($currentPath, '/'))) ?>"
                       class="lang-switch__item<?= $isCurrent ? ' is-active' : '' ?>"
                       aria-current="<?= $isCurrent ? 'true' : 'false' ?>"
                       hreflang="<?= e($code) ?>"
                       data-lang="<?= e($code) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <button type="button" class="masthead__burger" aria-label="<?= t('nav.menu') ?>" aria-expanded="false" aria-controls="masthead-mobile" data-menu-toggle>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <div class="masthead__mobile" id="masthead-mobile" role="navigation" aria-label="<?= t('nav.menu') ?>" hidden>
        <ul class="masthead__mobile-links">
            <?php foreach ($navModules as $slug): ?>
                <?php
                    $name = module($slug, 'name');
                    $url  = module_url($slug);
                    $active = $isActiveNav($slug);
                ?>
                <?php if ($name && $url): ?>
                    <li>
                        <a href="<?= e($url) ?>" class="masthead__mobile-link<?= $active ? ' is-active' : '' ?>" aria-current="<?= $active ? 'page' : 'false' ?>">
                            <?= e($name) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li class="masthead__mobile-actions">
                <a href="<?= e(url('/iletisim')) ?>" class="btn btn--primary btn--block"><?= t('nav.contact') ?></a>
            </li>
        </ul>
    </div>
</header>
