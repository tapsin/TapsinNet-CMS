<?php declare(strict_types=1); ?>
<?php
/**
 * JENERİK ALAN RENDERER
 *
 * ResourceController'ın `$form` tanımını tek noktadan çizer. Yeni bir alan
 * türü eklemek için yalnızca bu dosyadaki match'e bir dal eklemek yeterlidir.
 *
 * Beklenen değişkenler:
 *   $fkey    string  alan adı
 *   $fdef    array   alan tanımı (type, label, hint, col, options, lang, default)
 *   $frow    array   kayıt satırı (veya boş)
 *   $options array   select için dış seçenek listesi (opsiyonel)
 */
$fkey    = $fkey    ?? '';
$fdef    = $fdef    ?? [];
$frow    = $frow    ?? [];
$options = $options ?? [];

$type  = (string) ($fdef['type'] ?? 'text');
$label = (string) ($fdef['label'] ?? $fkey);
$hint  = (string) ($fdef['hint'] ?? '');
$col   = (int) ($fdef['col'] ?? 12);
$isTr  = (bool) ($fdef['lang'] ?? false);   // iki dilli alan
$error = error_for($fkey);
$req   = str_contains((string) ($fdef['rules'] ?? ''), 'required') ? ' <span class="req" aria-hidden="true">*</span>' : '';

/** Alan değeri: eski girdi > mevcut kayıt > varsayılan */
$value = static function (string $suffix = '') use ($fkey, $frow, $fdef, $isTr): string {
    $name = $isTr ? $fkey . $suffix : $fkey;
    $old  = old($name, null);
    if ($old !== null && $old !== '') {
        return (string) $old;
    }
    $row = $frow[$name] ?? '';
    if ($row !== '' && $row !== null) {
        return (string) $row;
    }
    return (string) ($fdef['default'] ?? '');
};

$inputId = $isTr ? $fkey . '_tr' : $fkey;
?>
<div class="field col-<?= e($col) ?> <?= $isTr ? 'field--bilingual' : '' ?>"
     <?= $error ? 'data-error="1"' : '' ?>>

    <label class="field__label" for="<?= e($inputId) ?>">
        <?= e($label) ?><?= $req ?>
        <?php if ($req): ?><span class="visually-hidden"> (<?= e(t('validation.required', ['attribute' => ''])) ?>)</span><?php endif; ?>
    </label>

    <?php if ($hint !== ''): ?>
        <p class="field__hint"><?= e(t($hint)) ?></p>
    <?php endif; ?>

    <?php /* ── İKİ DİLLİ ALAN: TR + EN ─────────────────────────────── */ ?>
    <?php if ($isTr): ?>
        <div class="field__pair">
            <span class="field__pair-label">Türkçe</span>
            <?php if (in_array($type, ['textarea', 'richtext', 'multitext'], true)): ?>
                <textarea id="<?= e($inputId) ?>" name="<?= e($fkey) ?>_tr"
                          class="textarea <?= $type === 'richtext' ? 'textarea--rich' : '' ?>"
                          rows="<?= $type === 'richtext' ? 12 : 3 ?>"
                          <?= $error ? 'aria-invalid="true"' : '' ?>><?= e($value('_tr')) ?></textarea>
            <?php else: ?>
                <input type="<?= e($type) ?>" id="<?= e($inputId) ?>" name="<?= e($fkey) ?>_tr"
                       class="input" value="<?= e($value('_tr')) ?>"
                       <?= $error ? 'aria-invalid="true"' : '' ?>>
            <?php endif; ?>

            <span class="field__pair-label">English</span>
            <?php if (in_array($type, ['textarea', 'richtext', 'multitext'], true)): ?>
                <textarea id="<?= e($fkey) ?>_en" name="<?= e($fkey) ?>_en"
                          class="textarea <?= $type === 'richtext' ? 'textarea--rich' : '' ?>" rows="3"><?= e($value('_en')) ?></textarea>
            <?php else: ?>
                <input type="<?= e($type) ?>" id="<?= e($fkey) ?>_en" name="<?= e($fkey) ?>_en"
                       class="input" value="<?= e($value('_en')) ?>">
            <?php endif; ?>
        </div>

    <?php /* ── GİZLİ ─────────────────────────────────────────────── */ ?>
    <?php elseif ($type === 'hidden'): ?>
        <input type="hidden" name="<?= e($fkey) ?>" value="<?= e($value()) ?>">

    <?php /* ── ONAY KUTUSU ───────────────────────────────────────── */ ?>
    <?php elseif ($type === 'checkbox'): ?>
        <?php $checked = (string) $value() === '1'; ?>
        <input type="hidden" name="<?= e($fkey) ?>" value="0">
        <label class="checkbox">
            <input type="checkbox" name="<?= e($fkey) ?>" value="1" <?= $checked ? 'checked' : '' ?>>
            <span><?= e($label) ?></span>
        </label>

    <?php /* ── RESİM ─────────────────────────────────────────────── */ ?>
    <?php elseif ($type === 'image'): ?>
        <?php $current = (string) ($frow[$fkey] ?? ''); $currentUrl = upload_url($current); ?>
        <?php if ($currentUrl !== null): ?>
            <div class="file-preview">
                <img src="<?= e($currentUrl) ?>" alt="" loading="lazy">
                <span>
                    <span class="file-preview__name"><?= e($current) ?></span><br>
                    <label class="checkbox">
                        <input type="checkbox" name="sil_<?= e($fkey) ?>" value="1">
                        <span><?= e(t('admin.delete')) ?></span>
                    </label>
                </span>
            </div>
        <?php endif; ?>
        <label class="dropzone" for="<?= e($fkey) ?>">
            <span>
                <?= e($currentUrl !== null ? t('admin.edit_button') : t('admin.add')) ?><br>
                <span class="u-faint">JPG · PNG · WEBP · AVIF — <?= e(t('upload.too_large', ['max' => '5 MB'])) ?></span>
            </span>
        </label>
        <input type="file" id="<?= e($fkey) ?>" name="<?= e($fkey) ?>" class="input"
               accept="image/jpeg,image/png,image/webp,image/avif,image/gif" data-preview>
        <?php if ($error): ?><p class="field__error"><?= e($error) ?></p><?php endif; ?>

    <?php /* ── DOSYA (PDF) ────────────────────────────────────────── */ ?>
    <?php elseif ($type === 'file'): ?>
        <?php $current = (string) ($frow[$fkey] ?? ''); $currentUrl = upload_url($current); ?>
        <?php if ($currentUrl !== null): ?>
            <div class="file-preview">
                <span aria-hidden="true">📄</span>
                <span>
                    <a href="<?= e($currentUrl) ?>" class="file-preview__name" target="_blank" rel="noopener"><?= e($current) ?></a><br>
                    <label class="checkbox">
                        <input type="checkbox" name="sil_<?= e($fkey) ?>" value="1">
                        <span><?= e(t('admin.delete')) ?></span>
                    </label>
                </span>
            </div>
        <?php endif; ?>
        <input type="file" id="<?= e($fkey) ?>" name="<?= e($fkey) ?>" class="input" accept="application/pdf" data-preview>
        <?php if ($error): ?><p class="field__error"><?= e($error) ?></p><?php endif; ?>

    <?php /* ── METİN ALANLARI ────────────────────────────────────── */ ?>
    <?php else: ?>
        <?php if ($type === 'textarea' || $type === 'richtext' || $type === 'multitext'): ?>
            <textarea id="<?= e($fkey) ?>" name="<?= e($fkey) ?>" rows="<?= $type === 'richtext' ? 12 : 3 ?>"
                      class="textarea <?= $type === 'richtext' ? 'textarea--rich' : '' ?>"
                      <?= $error ? 'aria-invalid="true"' : '' ?>><?= e($value()) ?></textarea>

        <?php elseif ($type === 'select'): ?>
            <?php
            $opts = $options[$fkey] ?? $fdef['options'] ?? [];
            $sel  = $value();
            ?>
            <select id="<?= e($fkey) ?>" name="<?= e($fkey) ?>" class="select" <?= $error ? 'aria-invalid="true"' : '' ?>>
                <?php if (!str_contains((string) ($fdef['rules'] ?? ''), 'required')): ?>
                    <option value="">—</option>
                <?php endif; ?>
                <?php foreach ($opts as $optKey => $opt): ?>
                    <?php
                    if (is_array($opt)) {
                        $optKey = $opt['key'] ?? '';
                        $optVal = $opt['label'] ?? $optKey;
                    } else {
                        $optVal = $opt;
                    }
                    ?>
                    <option value="<?= e((string) $optKey) ?>" <?= (string) $sel === (string) $optKey ? 'selected' : '' ?>>
                        <?= e((string) $optVal) ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($type === 'slug'): ?>
            <input type="text" id="<?= e($fkey) ?>" name="<?= e($fkey) ?>" class="input u-mono"
                   value="<?= e($value()) ?>" placeholder="otomatik"
                   data-slug-from="<?= e((string) ($fdef['slug_from'] ?? 'title_tr')) ?>">
            <p class="field__hint" data-slug-preview hidden></p>

        <?php else: ?>
            <input type="<?= e($type) ?>" id="<?= e($fkey) ?>" name="<?= e($fkey) ?>" class="input"
                   value="<?= e($value()) ?>"
                   <?= $type === 'number' ? 'step="any"' : '' ?>
                   <?= $error ? 'aria-invalid="true"' : '' ?>>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="field__error"><?= e($error) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($hint !== '' && !in_array($type, ['hidden', 'image', 'file'], true)): ?>
        <p class="field__hint"><?= e(t($hint)) ?></p>
    <?php endif; ?>
</div>
