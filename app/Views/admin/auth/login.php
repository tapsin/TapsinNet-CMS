<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e(locale()) ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(t('auth.title')) ?> · <?= e(setting('site_name', 'TapsinNet')) ?></title>
    <?= csrf_meta() ?>
    <link rel="stylesheet" href="<?= asset('assets/css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= asset('assets/img/favicon.svg') ?>">
</head>
<body>
<div class="login">
    <div class="login__card">
        <div class="login__brand">
            <img src="<?= asset('assets/img/favicon.svg') ?>" alt="" width="32" height="32">
            <span>
                <strong><?= e(setting('site_name', 'TapsinNet')) ?></strong><br>
                <span class="u-faint"><?= e(t('admin.dashboard')) ?></span>
            </span>
        </div>

        <h1 class="login__title"><?= e(t('auth.title')) ?></h1>
        <p class="login__subtitle"><?= e(t('auth.subtitle')) ?></p>

        <?= partial('admin.partials.flash') ?>

        <form method="post" action="<?= e(url('/admin/giris')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label class="field__label" for="login"><?= e(t('auth.login')) ?></label>
                <input type="text" id="login" name="login" class="input" required autocomplete="username"
                       autocapitalize="none" autocorrect="off" spellcheck="false" autofocus
                       value="<?= e(old('login', '')) ?>"
                       <?= error_for('login') ? 'aria-invalid="true"' : '' ?>>
                <?php if ($err = error_for('login')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
            </div>

            <div class="field">
                <label class="field__label" for="password"><?= e(t('auth.password')) ?></label>
                <div class="password-field">
                    <input type="password" id="password" name="password" class="input" required
                           autocomplete="current-password"
                           <?= error_for('password') ? 'aria-invalid="true"' : '' ?>>
                    <button type="button" class="password-field__toggle" data-password-toggle
                            data-label-show="<?= e(t('auth.show_password')) ?>"
                            data-label-hide="<?= e(t('auth.hide_password')) ?>"
                            aria-label="<?= e(t('auth.show_password')) ?>" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.2-5 9.5-5 9.5 5 9.5 5-3.2 5-9.5 5-9.5-5-9.5-5Z"></path><circle cx="12" cy="12" r="2.4"></circle></svg></button>
                </div>
                <?php if ($err = error_for('password')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
            </div>

            <label class="checkbox">
                <input type="checkbox" name="remember" value="1">
                <span>Bu cihazda oturumu açık tut</span>
            </label>

            <button type="submit" class="btn btn--primary btn--block"><?= e(t('auth.submit')) ?></button>
        </form>

        <p class="login__foot">
            <?= e(t('auth.secure_note')) ?><br>
            <a href="<?= e(url('/')) ?>">← <?= e(t('nav.home')) ?></a>
        </p>
    </div>
</div>
<script src="<?= asset('assets/js/admin.js') ?>" defer></script>
</body>
</html>
