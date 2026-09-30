<?php declare(strict_types=1); ?>
<?php /** HESAP — profil + parola. Tek sayfada iki ayrı form. */ ?>
<div class="grid grid-2">
    <form method="post" action="<?= e(url('/admin/hesap')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="panel">
            <div class="panel__head"><h2 class="panel__title"><?= e(t('user.title')) ?></h2></div>
            <div class="panel__body">
                <div class="field">
                    <label class="field__label" for="name"><?= e(t('user.name')) ?></label>
                    <input type="text" id="name" name="name" class="input" required
                           value="<?= e(old('name', $user['name'] ?? '')) ?>">
                    <?php if ($err = error_for('name')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="username"><?= e(t('user.username')) ?></label>
                    <input type="text" id="username" name="username" class="input"
                           value="<?= e(old('username', $user['username'] ?? '')) ?>"
                           autocapitalize="none" autocorrect="off" spellcheck="false"
                           maxlength="40" pattern="[A-Za-z0-9._\-]+"
                           aria-describedby="username-hint">
                    <p class="field__hint" id="username-hint"><?= e(t('user.username_hint')) ?></p>
                    <?php if ($err = error_for('username')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="email"><?= e(t('user.email')) ?></label>
                    <input type="email" id="email" name="email" class="input" required
                           value="<?= e(old('email', $user['email'] ?? '')) ?>">
                    <?php if ($err = error_for('email')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="panel__foot form-actions">
                <button type="submit" class="btn btn--primary"><?= e(t('admin.save')) ?></button>
            </div>
        </div>
    </form>

    <form method="post" action="<?= e(url('/admin/hesap/parola')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="panel">
            <div class="panel__head"><h2 class="panel__title"><?= e(t('user.password')) ?></h2></div>
            <div class="panel__body">
                <div class="field">
                    <label class="field__label" for="current_password"><?= e(t('user.current_password')) ?></label>
                    <div class="password-field">
                        <input type="password" id="current_password" name="current_password" class="input" required autocomplete="current-password">
                        <button type="button" class="password-field__toggle" data-password-toggle
                                data-label-show="<?= e(t('auth.show_password')) ?>"
                                data-label-hide="<?= e(t('auth.hide_password')) ?>"
                            aria-label="<?= e(t('auth.show_password')) ?>" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.2-5 9.5-5 9.5 5 9.5 5-3.2 5-9.5 5-9.5-5-9.5-5Z"></path><circle cx="12" cy="12" r="2.4"></circle></svg></button>
                    </div>
                    <?php if ($err = error_for('current_password')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="new_password"><?= e(t('user.new_password')) ?></label>
                    <input type="password" id="new_password" name="password" class="input" required
                           autocomplete="new-password" data-password-meter>
                    <div class="password-meter" id="pwMeter" aria-hidden="true">
                        <span class="password-meter__bar"></span><span class="password-meter__bar"></span>
                        <span class="password-meter__bar"></span><span class="password-meter__bar"></span>
                        <span class="password-meter__bar"></span>
                    </div>
                    <p class="field__hint" id="pwLabel"></p>
                    <?php if ($err = error_for('password')): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="field__label" for="password_confirmation"><?= e(t('user.confirm_password')) ?></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="input" required autocomplete="new-password">
                </div>
                <p class="field__hint"><?= e(t('user.password_hint')) ?></p>
            </div>
            <div class="panel__foot form-actions">
                <button type="submit" class="btn btn--primary"><?= e(t('admin.save')) ?></button>
            </div>
        </div>
    </form>
</div>
