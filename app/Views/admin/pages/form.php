<?php declare(strict_types=1); ?>
<?php
/**
 * JENERİK FORM — tüm içerik modülleri bu şablonu paylaşır.
 * $row, $form, $action, $title değişkenlerini ResourceController sağlar.
 */
$isEdit = !empty($row['id']);
?>
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="panel">
        <div class="panel__head">
            <h2 class="panel__title"><?= e($title) ?></h2>
            <?php if ($isEdit): ?>
                <div class="panel__actions">
                    <a href="<?= e(url('/admin/' . $module)) ?>" class="btn btn--ghost btn--sm"><?= e(t('admin.cancel')) ?></a>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel__body">
            <p class="field__hint"><?= e(t('admin.bilingual_hint')) ?></p>

            <div class="form-grid">
                <?php foreach ($form as $fkey => $fdef):
                    if (in_array($fkey, ['gallery', 'video_url'], true)) { continue; } ?>
                    <?= partial('admin.partials.field', [
                        'fkey'    => $fkey,
                        'fdef'    => $fdef,
                        'frow'    => $row,
                        'options' => $options ?? [],
                    ]) ?>
                <?php endforeach; ?>

                <?php /* Proje galerisi — çoklu görsel */ ?>
                <?php if ($module === 'projects'):
                    $shots = $isEdit ? \Models\Project::gallery($row) : []; ?>
                    <div class="field col-12">
                        <span class="field__label"><?= e(t('detail.gallery')) ?></span>
                        <input type="hidden" name="gallery" id="galleryValue"
                               value="<?= e($shots ? json_encode($shots, JSON_UNESCAPED_SLASHES) : '') ?>">
                        <?php if ($shots !== []): ?>
                            <div class="gallery-grid" id="galleryPreview">
                                <?php foreach ($shots as $i => $shot): ?>
                                    <figure>
                                        <img src="<?= e((string) upload_url($shot)) ?>" alt="" loading="lazy">
                                        <button type="button" data-gallery-remove="<?= e($i) ?>" aria-label="<?= e(t('admin.delete')) ?>">×</button>
                                    </figure>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <label class="dropzone" for="gallery_one">
                            <span><?= e(t('admin.add')) ?><br><span class="u-faint"><?= e(t('upload.too_large', ['max' => '5 MB'])) ?></span></span>
                        </label>
                        <input type="file" id="gallery_one" name="gallery_one" class="input"
                               accept="image/jpeg,image/png,image/webp,image/avif,image/gif" data-preview>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel__foot form-actions">
            <button type="submit" class="btn btn--primary"><?= e(t('admin.save')) ?></button>
            <a href="<?= e(url('/admin/' . $module)) ?>" class="btn btn--ghost"><?= e(t('admin.cancel')) ?></a>
            <?php if ($isEdit): ?>
                <a href="<?= e(url('/' . trim((string) \Core\ModuleRegistry::route($module), '/') . '/' . $row['slug'])) ?>"
                   class="btn--quiet" target="_blank" rel="noopener"><?= e(t('admin.preview')) ?> ↗</a>
            <?php endif; ?>
        </div>
    </div>
</form>
