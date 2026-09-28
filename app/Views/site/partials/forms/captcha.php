<?php declare(strict_types=1); ?>
<?php
/**
 * Form koruması alanı — Core\Captcha'nın etkin moduna göre basılır.
 *
 * · mod kapalıysa     hiçbir şey basılmaz (form yapısı değişmez)
 * · 'builtin'         soru + cevap kutusu + gizli tuzak alanı
 * · 'recaptcha'       Google'ın v2 onay kutusu (sunucu tarafı doğrular)
 *
 * @var string|null $captchaError  önceki gönderimden kalan hata
 */
use Core\Captcha;

$mode = Captcha::mode();
if ($mode === Captcha::MODE_OFF) {
    return;
}
$err = $captchaError ?? (error_for('captcha') ?: '');
?>
<div class="captcha">
    <?php if ($mode === Captcha::MODE_BUILTIN):
        $q = Captcha::builtinQuestion();
        ?>
        <label class="field__label" for="captcha-answer"><?= t('captcha.question_label', ['text' => '']) ?>
            <span class="captcha__q"><?= e($q['text']) ?></span>
        </label>
        <div class="captcha__row">
            <input type="text" id="captcha-answer" name="captcha_answer" class="input captcha__input"
                   inputmode="numeric" autocomplete="off" required
                   value="<?= e(old('captcha_answer', '')) ?>"
                   aria-describedby="captcha-help<?= $err ? ' captcha-error' : '' ?>"
                   <?= $err ? 'aria-invalid="true"' : '' ?>>
            <?php // gizli tuzak: botlar doldurur, insanlar görmez ?>
            <input type="text" name="captcha_hp" class="honeypot" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button type="button" class="captcha__reload" data-captcha-reload
                    aria-label="<?= t('captcha.reload') ?>" title="<?= t('captcha.reload') ?>">↻</button>
        </div>
        <p class="field__hint" id="captcha-help"><?= t('captcha.builtin_help') ?></p>
        <?php if ($err): ?>
            <p class="field__error" id="captcha-error" role="alert"><?= e($err) ?></p>
        <?php endif; ?>

    <?php else: // reCAPTCHA
        $site = Captcha::siteKey();
        ?>
        <div class="g-recaptcha" data-sitekey="<?= e($site) ?>"></div>
        <noscript>
            <p class="field__error"><?= t('captcha.noscript') ?></p>
        </noscript>
        <?php if ($err): ?>
            <p class="field__error" role="alert"><?= e($err) ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php if ($mode === Captcha::MODE_RECAPTCHA): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
