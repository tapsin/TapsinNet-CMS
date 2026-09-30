<?php declare(strict_types=1); ?>
<?php
/**
 * MODÜL YÖNETİMİ — her içerik türü bir modüldür.
 * Pasif modül: menüden düşer, public route'u 404 döner, ana sayfada
 * görünmez. İÇERİKLER SİLİNMEZ.
 */
$locked = ['messages', 'comments', 'search'];
?>
<div class="panel">
    <div class="panel__head">
        <h2 class="panel__title"><?= e(t('admin.module_status')) ?></h2>
    </div>
    <div class="panel__body">
        <p class="field__hint"><?= e(t('admin.module_note')) ?></p>
    </div>
</div>

<?php foreach ($groups as $groupKey => $groupLabel): ?>
    <?php
    $mods = array_values(array_filter($catalog, static fn (array $m): bool => ($m['group'] ?? 'content') === $groupKey));
    if ($mods === []) { continue; }
    ?>
    <section class="panel">
        <div class="panel__head">
            <h2 class="panel__title"><?= e($groupLabel) ?></h2>
            <span class="u-faint"><?= e(count($mods)) ?> <?= e(t('admin.module_content')) ?></span>
        </div>
        <div class="panel__body">
            <p class="module-order-help">Sıralamayı değiştirmek için kartları sürükleyip bırakın. Değişiklikler otomatik kaydedilir.</p>
            <div class="module-grid" data-module-sortable>
                <?php foreach ($mods as $m):
                    $slug    = $m['slug'];
                    $isLock  = in_array($slug, $locked, true);
                    $isOn    = (bool) $m['is_active'];
                    ?>
                    <div class="module-card <?= $isOn ? '' : 'is-off' ?>" draggable="true" data-module-card data-module-slug="<?= e($slug) ?>">
                        <span class="module-card__drag" title="Sürükleyerek sırala" aria-hidden="true">⠿</span>
                        <span class="module-card__head">
                            <span class="module-card__glyph" aria-hidden="true"><?= e($m['icon_glyph']) ?></span>
                            <span>
                                <span class="module-card__name"><?= e(\Core\ModuleRegistry::name($slug)) ?></span><br>
                                <span class="u-mono u-faint"><?= e($slug) ?></span>
                            </span>
                        </span>

                        <p class="module-card__desc"><?= e(\Core\ModuleRegistry::desc($slug)) ?></p>

                        <div class="module-card__badges">
                            <span class="badge badge--muted"><?= e(t('admin.module_content')) ?>: <?= e($m['count'] === null ? '—' : number_format((int) $m['count'], 0, ',', '.')) ?></span>
                            <?php if ($m['show_home']): ?><span class="badge badge--accent"><?= e(t('admin.module_home')) ?></span><?php endif; ?>
                            <?php if ($isLock): ?><span class="badge badge--info"><?= e(t('admin.module_system')) ?></span><?php endif; ?>
                        </div>

                        <div class="module-card__foot">
                            <form method="post" action="<?= e(url('/admin/moduller/islem')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="slug" value="<?= e($slug) ?>">
                                <label class="module-toggle">
                                    <input type="checkbox" name="aktif" value="1"
                                           data-module-toggle
                                           <?= $isOn ? 'checked' : '' ?> <?= $isLock ? 'disabled' : '' ?>>
                                    <span class="module-toggle__text"><?= e($isOn ? t('admin.active') : t('admin.passive')) ?></span>
                                </label>
                                <noscript><button type="submit" name="islem" value="<?= $isOn ? 'pasif' : 'aktif' ?>" class="btn btn--sm"><?= e(t('admin.save')) ?></button></noscript>
                            </form>
                            <form method="post" action="<?= e(url('/admin/moduller/islem')) ?>" class="module-order-form" data-module-order-form>
                                <?= csrf_field() ?>
                                <input type="hidden" name="islem" value="sira">
                                <input type="hidden" name="slug" value="<?= e($slug) ?>">
                                <input type="hidden" name="sirala" value="<?= e((string) $m['sort_order']) ?>" data-module-order-input>
                            </form>

                            <span class="u-nowrap">
                                <?php
                                // Yol yine admin_path'ten gelir; rota adından
                                // türetmek Türkçe kayıtlı rotalarda 404 verir.
                                $ap = \Core\ModuleRegistry::adminPath($m['slug']);
                                ?>
                                <?= ($m['admin'] && $ap)
                                    ? '<a href="' . e(url('/admin/' . $ap)) . '">' . e(t('admin.actions')) . '</a>'
                                    : '' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endforeach; ?>
