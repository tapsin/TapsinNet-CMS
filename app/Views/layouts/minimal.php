<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e($locale) ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrf_meta() ?>

    <link rel="preload" as="style" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">

    <link rel="preload" as="style" href="<?= asset('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/fonts.css') ?>">

    <link rel="preload" as="font" type="font/woff2" crossorigin href="<?= asset('assets/fonts/display-latin-ext.woff2') ?>">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="<?= asset('assets/fonts/body-latin-ext.woff2') ?>">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="<?= asset('assets/fonts/mono-latin-ext.woff2') ?>">

    <title><?= e($metaTitle ?? $siteName) ?></title>
    <meta name="description" content="<?= e($metaDesc ?? setting('meta_description', '')) ?>">
    <link rel="canonical" href="<?= e(url($currentPath)) ?>">

    <meta name="theme-color" content="#c85c1a">

    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">
</head>
<body>
    <a href="#main" class="skip-link"><?= t('app.skip') ?></a>

    <main id="main" role="main" class="wrap">
        <?= partial('site.partials.flash') ?>
        <?= content() ?>
    </main>

    <script src="<?= asset('assets/js/app.js') ?>" defer></script>
<!-- impeccable-live-start -->
<script src="http://localhost:8400/live.js?token=797cbe93-e71a-42c2-ba61-79a843e1a84d"></script>
<!-- impeccable-live-end -->
</body>
</html>