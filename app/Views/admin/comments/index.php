<?php declare(strict_types=1); ?>
<?php /** YORUM ONAY KUYRUĞU */ ?>
<div class="stat-grid">
    <div class="stat stat--accent"><span class="stat__value"><?= e(number_format((int) $counts['pending'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.pending')) ?></span></div>
    <div class="stat"><span class="stat__value"><?= e(number_format((int) $counts['approved'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.approved')) ?></span></div>
    <div class="stat"><span class="stat__value"><?= e(number_format((int) $counts['spam'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.spam')) ?></span></div>
</div>

<div class="toolbar">
    <div class="toolbar__group">
        <?php foreach (['pending' => t('admin.pending'), 'approved' => t('admin.approved'), 'spam' => t('admin.spam')] as $key => $label): ?>
            <a href="<?= e(url('/admin/yorumlar', $key === 'pending' ? [] : ['filter' => $key])) ?>"
               class="filter-chip <?= $filter === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <div class="empty-state__glyph" aria-hidden="true">✱</div>
        <h2 class="empty-state__title"><?= e(t('admin.no_comments')) ?></h2>
    </div>
<?php else: ?>
    <div class="comment-queue">
        <?php foreach ($rows as $c): $id = (int) $c['id']; ?>
            <article class="comment-card">
                <div class="comment-card__head">
                    <span class="comment-card__author"><?= e($c['author_name']) ?></span>
                    <span><?= e($c['author_email']) ?></span>
                    <span><?= e(time_ago((string) $c['created_at'])) ?></span>
                    <span class="badge badge--muted"><?= e($c['content_type']) ?> #<?= e((int) $c['entity_id']) ?></span>
                    <?php if ((int) $c['is_approved'] === 1): ?><span class="badge badge--success"><?= e(t('admin.approved')) ?></span><?php endif; ?>
                    <?php if ((int) $c['is_spam'] === 1): ?><span class="badge badge--danger"><?= e(t('admin.spam')) ?></span><?php endif; ?>
                </div>
                <p class="comment-card__body"><?= e($c['body']) ?></p>

                <!-- Yönetici notu — içerik kutusunda tutulur, ziyaretçiye
                     gösterilmez. Kaydet düğmesi ayrı form; aynı kartın
                     eylem formuyla iç içe geçmemesi için ayrı yazıldı. -->
                <form method="post" action="<?= e(url('/admin/yorumlar/islem/' . $id)) ?>"
                      class="comment-card__note-form">
                    <?= csrf_field() ?>
                    <label class="sr-only" for="note-<?= (int) $id ?>"><?= e(t('admin.note_saved')) ?></label>
                    <input type="hidden" name="islem" value="not">
                    <textarea id="note-<?= (int) $id ?>" name="admin_note" class="input"
                              rows="2" maxlength="2000"
                              placeholder="<?= e(t('admin.comment_note_ph')) ?>"><?= e((string) ($c['admin_note'] ?? '')) ?></textarea>
                    <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('admin.note_save')) ?></button>
                </form>

                <form method="post" action="<?= e(url('/admin/yorumlar/islem/' . $id)) ?>" class="btn-row">
                    <?= csrf_field() ?>
                    <?php if ((int) $c['is_approved'] !== 1): ?>
                        <button type="submit" name="islem" value="onayla" class="btn btn--primary btn--sm"><?= e(t('admin.approve')) ?></button>
                    <?php else: ?>
                        <button type="submit" name="islem" value="onaykaldir" class="btn btn--ghost btn--sm"><?= e(t('admin.unapprove')) ?></button>
                    <?php endif; ?>
                    <?php if ((int) $c['is_spam'] !== 1): ?>
                        <button type="submit" name="islem" value="spam" class="btn btn--ghost btn--sm"><?= e(t('admin.mark_spam')) ?></button>
                    <?php else: ?>
                        <button type="submit" name="islem" value="spamdegil" class="btn btn--ghost btn--sm"><?= e(t('admin.not_spam')) ?></button>
                    <?php endif; ?>
                    <button type="submit" name="islem" value="sil" class="btn btn--danger btn--sm"
                            data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= e(t('admin.delete')) ?></button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= $paginator->links('admin.partials.pagination') ?>
