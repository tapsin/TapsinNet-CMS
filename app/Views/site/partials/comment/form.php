<?php declare(strict_types=1); ?>
<?php
/**
 * Yorum formu. Alan adları CommentController@store ile birebir eşleşmelidir:
 * content_type · entity_id · author_name · author_email · body
 * (Eski sürümde module/item_id/name kullanılıyordu ve hiçbir zaman eşleşmedi —
 *  yorum gönderimi sessizce başarısızdı.)
 */
?>
<form class="form comment-form" action="<?= e(url('/yorumlar')) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="content_type" value="<?= e($module) ?>">
    <input type="hidden" name="entity_id" value="<?= (int) $itemId ?>">
    <input type="hidden" name="website" class="honeypot" tabindex="-1" autocomplete="off">

    <div class="form-grid">
        <div class="field col-6">
            <label for="comment-name" class="field__label"><?= t('comment.name') ?></label>
            <input type="text" id="comment-name" name="author_name" class="input" required
                   value="<?= e(old('author_name', '')) ?>"
                   placeholder="<?= t('comment.field.name') ?>"
                   aria-describedby="comment-name-error">
            <?php if ($err = error_for('author_name')): ?>
                <p id="comment-name-error" class="field__error" role="alert"><?= e($err) ?></p>
            <?php endif; ?>
        </div>

        <div class="field col-6">
            <label for="comment-email" class="field__label"><?= t('comment.email') ?></label>
            <input type="email" id="comment-email" name="author_email" class="input" required
                   value="<?= e(old('author_email', '')) ?>"
                   placeholder="<?= t('comment.field.email') ?>"
                   aria-describedby="comment-email-error">
            <?php if ($err = error_for('author_email')): ?>
                <p id="comment-email-error" class="field__error" role="alert"><?= e($err) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="field col-12">
        <label for="comment-body" class="field__label"><?= t('comment.body') ?></label>
        <textarea id="comment-body" name="body" class="textarea" rows="5" required
                  placeholder="<?= t('comment.field.body') ?>"
                  aria-describedby="comment-body-error"><?= e(old('body', '')) ?></textarea>
        <?php if ($err = error_for('body')): ?>
            <p id="comment-body-error" class="field__error" role="alert"><?= e($err) ?></p>
        <?php endif; ?>
    </div>

    <?= partial('site.partials.forms.captcha') ?>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary" data-submit-btn>
            <?= t('comment.submit') ?>
        </button>
    </div>
</form>