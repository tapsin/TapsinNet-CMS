<?php declare(strict_types=1); ?>
<?php
/**
 * AYARLAR — SettingController::SCHEMA'daki gruplar sekme olarak basılır.
 * Tek form: `lang => true` alanlar iki kolon (TR/EN) gelir.
 */
$groups = $schema;
$active = (string) (current_query()['grup'] ?? array_key_first($groups));
$get = static function (string $key, string $lang) use ($values): string {
    $row = $values[$key] ?? null;
    return is_array($row) ? (string) ($row['value_' . $lang] ?? '') : '';
};
?>
<div class="tabs" role="tablist" aria-label="<?= e(t('admin.settings')) ?>">
    <?php foreach ($groups as $gKey => $gDef): ?>
        <a href="<?= e(url($currentPath, ['grup' => $gKey])) ?>" class="tab"
           role="tab" aria-selected="<?= $gKey === $active ? 'true' : 'false' ?>">
            <span aria-hidden="true"><?= e($gDef['icon']) ?></span> <?= e(t($gDef['label'])) ?>
        </a>
    <?php endforeach; ?>
</div>

<form method="post" action="<?= e(url('/admin/ayarlar')) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <?php foreach ($groups as $gKey => $gDef): ?>
        <section class="panel" <?= $gKey === $active ? '' : 'hidden' ?>>
            <div class="panel__head">
                <h2 class="panel__title"><?= e(t($gDef['label'])) ?></h2>
                <span class="u-faint"><?= e(count($gDef['fields'])) ?> <?= e(t('admin.group')) ?></span>
            </div>
            <div class="panel__body">
                <div class="form-grid">
                    <?php foreach ($gDef['fields'] as $key => $f):
                        $type = (string) $f['type'];
                        $lang = (bool) ($f['lang'] ?? false);
                        $val  = $get($key, 'tr');
                        $en   = $get($key, 'en');
                        $err  = error_for($key);
                        ?>
                        <div class="field col-<?= e((int) ($f['col'] ?? 12)) ?> <?= $lang ? 'field--bilingual' : '' ?>" <?= $err ? 'data-error="1"' : '' ?>>
                            <label class="field__label" for="s-<?= e($key) ?>"><?= e($f['label']) ?></label>
                            <?php if (!empty($f['hint'])): ?>
                                <p class="field__hint"><?= e($f['hint']) ?></p>
                            <?php endif; ?>

                            <?php if ($type === 'bool'): ?>
                                <input type="hidden" name="<?= e($key) ?>" value="0">
                                <label class="checkbox checkbox--switch">
                                    <input type="checkbox" name="<?= e($key) ?>" value="1"
                                           <?= in_array(strtolower($val), ['1', 'true', 'on', 'yes'], true) ? 'checked' : '' ?>>
                                    <span><?= e(t('admin.active')) ?></span>
                                </label>

                            <?php elseif ($type === 'image'): ?>
                                <?php $u = upload_url($val !== '' ? $val : null); ?>
                                <?php if ($u !== null): ?>
                                    <div class="file-preview">
                                        <img src="<?= e($u) ?>" alt="" loading="lazy">
                                        <span>
                                            <span class="file-preview__name"><?= e($val) ?></span><br>
                                            <label class="checkbox">
                                                <input type="checkbox" name="sil_<?= e($key) ?>" value="1">
                                                <span><?= e(t('admin.delete')) ?></span>
                                            </label>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="s-<?= e($key) ?>" name="<?= e($key) ?>" class="input"
                                       accept="image/jpeg,image/png,image/webp,image/avif,image/gif" data-preview>

                            <?php elseif ($type === 'textarea'): ?>
                                <?php if ($lang): ?>
                                    <div class="field__pair">
                                        <span class="field__pair-label">Türkçe</span>
                                        <textarea id="s-<?= e($key) ?>_tr" name="<?= e($key) ?>_tr" class="textarea" rows="4"><?= e(old($key . '_tr', $val)) ?></textarea>
                                        <span class="field__pair-label">English</span>
                                        <textarea id="s-<?= e($key) ?>_en" name="<?= e($key) ?>_en" class="textarea" rows="4"><?= e(old($key . '_en', $en)) ?></textarea>
                                    </div>
                                <?php else: ?>
                                    <textarea id="s-<?= e($key) ?>" name="<?= e($key) ?>" class="textarea" rows="4"><?= e(old($key, $val)) ?></textarea>
                                <?php endif; ?>

                            <?php else: ?>
                                <?php if ($lang): ?>
                                    <div class="field__pair">
                                        <span class="field__pair-label">Türkçe</span>
                                        <input type="<?= e($type) ?>" id="s-<?= e($key) ?>" name="<?= e($key) ?>_tr" class="input"
                                               value="<?= e(old($key . '_tr', $val)) ?>">
                                        <span class="field__pair-label">English</span>
                                        <input type="<?= e($type) ?>" name="<?= e($key) ?>_en" class="input"
                                               value="<?= e(old($key . '_en', $en)) ?>">
                                    </div>
                                <?php else: ?>
                                    <input type="<?= e($type) ?>" id="s-<?= e($key) ?>" name="<?= e($key) ?>" class="input"
                                           value="<?= e(old($key, $val)) ?>" <?= $err ? 'aria-invalid="true"' : '' ?>>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($err): ?><p class="field__error"><?= e($err) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="panel">
        <div class="panel__foot form-actions">
            <button type="submit" class="btn btn--primary"><?= e(t('admin.save')) ?></button>
            <span class="u-faint"><?= e(t('admin.bilingual_hint')) ?></span>
        </div>
    </div>
</form>
