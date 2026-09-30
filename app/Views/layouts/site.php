<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e($locale) ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrf_meta() ?>

    <link rel="preload" as="style" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="preload" as="style" href="<?= asset('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">

    <title><?= e($metaTitle ?? $siteName) ?></title>
    <meta name="description" content="<?= e($metaDesc ?? setting('meta_description', '')) ?>">
    <link rel="canonical" href="<?= e(url($currentPath)) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($metaTitle ?? $siteName) ?>">
    <meta property="og:description" content="<?= e($metaDesc ?? setting('meta_description', '')) ?>">
    <meta property="og:url" content="<?= e(url($currentPath)) ?>">
    <?php if (!empty($ogImage ?? null)): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">

    <meta name="theme-color" content="#000000">

    <?php $siteFavicon = upload_url(setting('favicon_image', '')) ?: asset('assets/img/favicon.svg'); ?>
    <link rel="icon" href="<?= e($siteFavicon) ?>">
    <link rel="alternate" hreflang="tr" href="<?= e(url('/' . ltrim($currentPath, '/'))) ?>">
    <link rel="alternate" hreflang="en" href="<?= e(url('/en/' . ltrim($currentPath, '/'))) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e(url('/' . ltrim($currentPath, '/'))) ?>">
</head>
<body>
    <a href="#main" class="skip-link"><?= t('app.skip') ?></a>

    <?= partial('site.partials.masthead', [
        'navModules' => $navModules,
        'currentRoute' => $currentRoute,
        'locale' => $locale,
    ]) ?>

    <main id="main" role="main">
        <?= partial('site.partials.flash') ?>
        <?= content() ?>
    </main>

    <?= partial('site.partials.footer', [
        'footerPages' => $footerPages ?? [],
        'siteName' => $siteName,
    ]) ?>

    <?= partial('site.partials.lightbox') ?>
    <?= partial('site.partials.video-modal') ?>

    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
</body>
</html>
