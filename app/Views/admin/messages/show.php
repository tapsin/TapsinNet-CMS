<?php declare(strict_types=1); ?>
<?php /** MESAJ DETAYI — okundu işaretlenir, yönetici notu kaydedilir. */ ?>
<div class="grid grid-2">
    <div>
        <div class="panel">
            <div class="panel__head">
                <h2 class="panel__title"><?= e($row['subject'] ?: t('admin.messages')) ?></h2>
                <span class="u-faint"><?= e(format_date((string) $row['created_at'], 'd.m.Y H:i')) ?></span>
            </div>
            <div class="panel__body">
                <div class="side-meta">
                    <div class="side-meta__row"><span class="side-meta__key"><?= e(t('contact.field.name')) ?></span><span class="side-meta__value"><?= e($row['name']) ?></span></div>
                    <div class="side-meta__row"><span class="side-meta__key"><?= e(t('contact.field.email')) ?></span><span class="side-meta__value"><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></span></div>
                    <?php if (!empty($row['phone'])): ?>
                        <div class="side-meta__row"><span class="side-meta__key"><?= e(t('contact.field.phone')) ?></span><span class="side-meta__value"><?= e($row['phone']) ?></span></div>
                    <?php endif; ?>
                    <div class="side-meta__row"><span class="side-meta__key"><?= e(t('admin.ip')) ?></span><span class="side-meta__value u-mono"><?= e($row['ip'] ?? '—') ?></span></div>
                    <div class="side-meta__row"><span class="side-meta__key"><?= e(t('admin.system')) ?></span><span class="side-meta__value u-faint"><?= e(str_limit((string) ($row['user_agent'] ?? ''), 70)) ?></span></div>
                </div>

                <div class="message-body"><?= e($row['message']) ?></div>
            </div>
            <div class="panel__foot panel__actions">
                <a href="mailto:<?= e($row['email']) ?>?subject=<?= rawurlencode('Re: ' . (string) ($row['subject'] ?: '')) ?>" class="btn btn--primary btn--sm"><?= e(t('admin.reply')) ?></a>
                <a href="<?= e(url('/admin/mesajlar')) ?>" class="btn btn--ghost btn--sm"><?= e(t('admin.back')) ?></a>
            </div>
        </div>
    </div>

    <div>
        <form method="post" action="<?= e(url('/admin/mesajlar/islem/' . (int) $row['id'])) ?>">
            <?= csrf_field() ?>
            <div class="panel">
                <div class="panel__head"><h2 class="panel__title"><?= e(t('admin.admin_note')) ?></h2></div>
                <div class="panel__body">
                    <div class="field">
                        <textarea name="admin_note" class="textarea" rows="6"
                                  placeholder="<?= e(t('admin.admin_note')) ?>"><?= e((string) ($row['admin_note'] ?? '')) ?></textarea>
                        <p class="field__hint"><?= e(t('admin.admin_note')) ?> — ziyaretçiye gösterilmez.</p>
                    </div>
                </div>
                <div class="panel__foot panel__actions">
                    <button type="submit" name="islem" value="not" class="btn btn--primary btn--sm"><?= e(t('admin.save')) ?></button>
                    <button type="submit" name="islem" value="okunmadi" class="btn btn--ghost btn--sm"><?= e(t('admin.unread_short')) ?></button>
                    <button type="submit" name="islem" value="yildiz" class="btn btn--ghost btn--sm">★</button>
                    <button type="submit" name="islem" value="arsiv" class="btn btn--ghost btn--sm"><?= e(t('admin.archive')) ?></button>
                    <button type="submit" name="islem" value="sil" class="btn btn--danger btn--sm"
                            data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= e(t('admin.delete')) ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
