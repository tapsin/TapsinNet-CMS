<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e($locale) ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? t('admin.dashboard')) ?> · <?= e($siteName) ?></title>
    <?= csrf_meta() ?>
    <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">
</head>
<body>
<div class="admin">

    <?= partial('admin.partials.sidebar', ['badge' => \Models\Message::badge()]) ?>

    <div class="admin__main">
        <?= partial('admin.partials.topbar', [
            'title'   => $title ?? t('admin.dashboard'),
            'authUser' => $authUser,
        ]) ?>

        <main class="admin__content" role="main">
            <?= partial('admin.partials.flash') ?>
            <?= content() ?>
        </main>
    </div>
</div>

<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
<!-- impeccable-live-start -->
<script src="http://localhost:8400/live.js?token=797cbe93-e71a-42c2-ba61-79a843e1a84d"></script>
<!-- impeccable-live-end -->
</body>
</html>
