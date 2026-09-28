<?php declare(strict_types=1); ?>
<?php
/** HATA SAYFASI — production'da yığın izi gösterilmez, yalnızca referans kodu. */
$status  = (int) ($status ?? 500);
$isNotFound = $status === 404;
?>
<!doctype html>
<html lang="<?= e(\Core\Translator::lang()) ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($status) ?> · <?= e($title ?? '') ?></title>
    <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/theme.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">
</head>
<body>
    <div class="error-page">
        <div>
            <p class="error-page__code"><?= e($status) ?></p>
            <h1 class="error-page__title"><?= e($title ?? t('errors.generic')) ?></h1>
            <p class="error-page__text">
                <?php if ($isNotFound): ?>
                    <?= e(t('errors.404.text')) ?>
                <?php elseif ($status >= 500): ?>
                    <?= e(t('errors.500.text')) ?>
                <?php else: ?>
                    <?= e($message ?? '') ?>
                <?php endif; ?>
            </p>

            <div class="btn-row">
                <a href="<?= e(url('/')) ?>" class="btn btn--primary"><?= e(t('errors.404.cta')) ?></a>
                <?php if ($isNotFound): ?>
                    <a href="<?= e(url('/ara')) ?>" class="btn btn--ghost"><?= e(t('errors.404.search')) ?></a>
                <?php endif; ?>
            </div>

            <?php if ($status >= 500 && !empty($ref)): ?>
                <p class="error-page__ref">
                    <?= e(t('errors.ref')) ?>: <code><?= e($ref) ?></code>
                </p>
            <?php endif; ?>

            <?php if (!empty($debug) && !empty($trace)): ?>
                <pre class="error-page__trace"><?= e($trace) ?></pre>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
