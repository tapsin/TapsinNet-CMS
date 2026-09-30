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
    <?php $adminFavicon = upload_url(setting('favicon_image', '')) ?: asset('assets/img/favicon.svg'); ?>
    <link rel="icon" href="<?= e($adminFavicon) ?>">
</head>
<body>
<div class="admin">

    <?= partial('admin.partials.sidebar', ['badge' => \Models\Message::badge(), 'logoUrl' => upload_url(setting('logo_image', ''))]) ?>

    <div class="admin__main">
        <?= partial('admin.partials.topbar', [
            'title'   => $title ?? t('admin.dashboard'),
            'authUser' => $authUser,
            'logoUrl'  => upload_url(setting('logo_image', '')),
        ]) ?>

        <main class="admin__content" role="main">
            <?= partial('admin.partials.flash') ?>
            <?= content() ?>
        </main>
    </div>
</div>

<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
</body>
</html>
