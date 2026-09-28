<?php declare(strict_types=1); ?>
<form class="form" action="<?= e(url('/iletisim')) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="source" value="contact-form">
    <input type="hidden" name="website" class="honeypot" tabindex="-1" autocomplete="off">

    <div class="form-grid">
        <div class="field col-6">
            <label for="contact-name" class="field__label"><?= t('contact.field.name') ?></label>
            <input type="text" id="contact-name" name="name" class="input" required
                   value="<?= e(old('contact-name', '')) ?>"
                   placeholder="<?= t('contact.name_ph') ?>"
                   aria-describedby="contact-name-error">
            <?php if ($err = error_for('name')): ?>
                <p id="contact-name-error" class="field__error" role="alert"><?= e($err) ?></p>
            <?php endif; ?>
        </div>

        <div class="field col-6">
            <label for="contact-email" class="field__label"><?= t('contact.field.email') ?></label>
            <input type="email" id="contact-email" name="email" class="input" required
                   value="<?= e(old('contact-email', '')) ?>"
                   placeholder="<?= t('contact.email_ph') ?>"
                   aria-describedby="contact-email-error">
            <?php if ($err = error_for('email')): ?>
                <p id="contact-email-error" class="field__error" role="alert"><?= e($err) ?></p>
            <?php endif; ?>
        </div>

        <div class="field col-6">
            <label for="contact-phone" class="field__label"><?= t('contact.field.phone') ?></label>
            <input type="tel" id="contact-phone" name="phone" class="input"
                   value="<?= e(old('contact-phone', '')) ?>"
                   placeholder="<?= t('contact.phone_ph') ?>"
                   aria-describedby="contact-phone-error">
            <?php if ($err = error_for('phone')): ?>
                <p id="contact-phone-error" class="field__error" role="alert"><?= e($err) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($services)): ?>
            <div class="field col-6">
                <label for="contact-subject" class="field__label"><?= t('contact.field.subject') ?></label>
                <select id="contact-subject" name="subject" class="select" aria-describedby="contact-subject-error">
                    <option value=""><?= t('contact.subject_ph') ?></option>
                    <?php foreach ($services as $svc): ?>
                        <option value="<?= e(loc($svc, 'title')) ?>"
                            <?= old('contact-subject') === loc($svc, 'title') ? 'selected' : '' ?>>
                            <?= e(loc($svc, 'title')) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="other" <?= old('contact-subject') === 'other' ? 'selected' : '' ?>><?= t('contact.subject_other') ?></option>
                </select>
                <?php if ($err = error_for('subject')): ?>
                    <p id="contact-subject-error" class="field__error" role="alert"><?= e($err) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="field col-6">
                <label for="contact-subject" class="field__label"><?= t('contact.field.subject') ?></label>
                <input type="text" id="contact-subject" name="subject" class="input" required
                       value="<?= e(old('contact-subject', '')) ?>"
                       placeholder="<?= t('contact.subject_ph') ?>"
                       aria-describedby="contact-subject-error">
                <?php if ($err = error_for('subject')): ?>
                    <p id="contact-subject-error" class="field__error" role="alert"><?= e($err) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="field col-12">
        <label for="contact-message" class="field__label"><?= t('contact.field.message') ?></label>
        <textarea id="contact-message" name="message" class="textarea" rows="6" required
                  placeholder="<?= t('contact.message_ph') ?>"
                  aria-describedby="contact-message-error"><?= e(old('contact-message', '')) ?></textarea>
        <?php if ($err = error_for('message')): ?>
            <p id="contact-message-error" class="field__error" role="alert"><?= e($err) ?></p>
        <?php endif; ?>
    </div>

    <div class="field field--checkbox col-12">
        <input type="checkbox" id="contact-kvkk" name="kvkk" class="checkbox__input" required
               value="1" <?= old('contact-kvkk') ? 'checked' : '' ?>
               aria-describedby="contact-kvkk-error">
        <label for="contact-kvkk" class="field__label field__label--check">
            <?= t('contact.kvkk') ?>
        </label>
        <?php if ($err = error_for('kvkk')): ?>
            <p id="contact-kvkk-error" class="field__error field__error--block" role="alert"><?= e($err) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--block" data-submit-btn>
            <?= t('contact.submit') ?>
        </button>
    </div>
</form>