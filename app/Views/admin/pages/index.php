<?php declare(strict_types=1); ?>
<?php
/**
 * JENERİK LİSTE — tüm içerik modülleri bu şablonu paylaşır.
 * Değişkenler: ResourceController::index() tarafından sağlanır.
 *   $paginator, $rows, $module, $moduleName, $term, $filter,
 *   $sortCol, $sortDir, $filters, $columns, $stats
 */
$base     = url('/admin/' . $module);
$formUrl  = $base . '/islem';
$editBase = $base . '/duzenle/';
?>

<div class="stat-grid">
    <?php if (isset($stats['total'])): ?>
        <div class="stat"><span class="stat__value"><?= e(number_format((int) $stats['total'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.total')) ?></span></div>
        <div class="stat"><span class="stat__value"><?= e(number_format((int) ($stats['active'] ?? 0), 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.active')) ?></span></div>
        <div class="stat"><span class="stat__value"><?= e(number_format((int) ($stats['passive'] ?? 0), 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.passive')) ?></span></div>
        <div class="stat stat--accent"><span class="stat__value"><?= e(number_format((int) ($stats['week'] ?? 0), 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.week')) ?></span></div>
    <?php endif; ?>
    <?php if (isset($stats['expiring'])): ?>
        <div class="stat stat--warning"><span class="stat__value"><?= e(number_format((int) $stats['expiring'], 0, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.expiring')) ?></span></div>
    <?php endif; ?>
    <?php if (isset($stats['avg'])): ?>
        <div class="stat"><span class="stat__value"><?= e(number_format((float) $stats['avg'], 1, ',', '.')) ?></span><span class="stat__label"><?= e(t('admin.stats.avg')) ?></span></div>
    <?php endif; ?>
</div>

<div class="toolbar">
    <form class="toolbar__group toolbar__grow" method="get" action="<?= e($base) ?>" role="search">
        <?php foreach (current_query() as $ck => $cv): ?>
            <?php if ($ck !== 'q'): ?><input type="hidden" name="<?= e($ck) ?>" value="<?= e($cv) ?>"><?php endif; ?>
        <?php endforeach; ?>
        <label class="visually-hidden" for="q"><?= e(t('admin.search_ph')) ?></label>
        <input type="search" id="q" name="q" class="input" value="<?= e($term) ?>"
               placeholder="<?= e(t('admin.search_ph')) ?>" autocomplete="off" data-search>
    </form>

    <div class="toolbar__group">
        <label class="visually-hidden" for="durum"><?= e(t('admin.all_status')) ?></label>
        <select id="durum" name="durum" class="select u-nowrap" onchange="this.form.submit()"
                style="width:auto">
            <option value=""><?= e(t('admin.all_status')) ?></option>
            <option value="aktif" <?= $filter === 'aktif' ? 'selected' : '' ?>><?= e(t('admin.active')) ?></option>
            <option value="pasif" <?= $filter === 'pasif' ? 'selected' : '' ?>><?= e(t('admin.passive')) ?></option>
        </select>
        <noscript><button type="submit" class="btn btn--ghost btn--sm"><?= e(t('admin.save')) ?></button></noscript>
    </div>

    <?php foreach ($filters as $fk => $fopts): ?>
        <?php if ($fopts === []) { continue; }
        $active = (string) (current_query()[$fk] ?? ''); ?>
        <div class="toolbar__group">
            <label class="visually-hidden" for="flt-<?= e($fk) ?>"></label>
            <select id="flt-<?= e($fk) ?>" class="select u-nowrap" onchange="this.form.submit()" data-filter-nav>
                <?php foreach ($fopts as $opt): ?>
                    <option value="<?= e((string) $opt['key']) ?>" <?= $active === (string) $opt['key'] ? 'selected' : '' ?>>
                        <?= e($opt['label']) ?><?= isset($opt['count']) && $opt['count'] > 0 ? ' (' . (int) $opt['count'] . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>

    <span class="toolbar__sep" aria-hidden="true"></span>
    <a href="<?= e($base . '/ekle') ?>" class="btn btn--primary"><?= e(t('admin.add')) ?> +</a>
</div>

<?php if ($rows === []): ?>
    <div class="empty-state">
        <div class="empty-state__glyph" aria-hidden="true">◇</div>
        <h2 class="empty-state__title"><?= e(t('admin.no_records')) ?></h2>
        <p class="empty-state__text"><?= e(t('admin.no_records_hint')) ?></p>
        <div class="btn-row"><a href="<?= e($base . '/ekle') ?>" class="btn btn--primary"><?= e(t('admin.add')) ?></a></div>
    </div>
<?php else: ?>
    <form method="post" action="<?= e($formUrl) ?>/toplu" id="bulkForm">
        <?= csrf_field() ?>
        <div class="panel">
            <div class="table-scroll">
                <table class="table table--sortable">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <label class="checkbox">
                                    <input type="checkbox" data-bulk-all aria-label="<?= e(t('admin.select_all')) ?>">
                                </label>
                            </th>
                            <?php foreach ($columns as $colKey => $col): ?>
                                <?php
                                $label = t((string) $col['label'], [], (string) $col['label']);
                                $isSortable = in_array($colKey, ['title_tr','name','client','category','created_at','sort_order','views','rating','issue_date','published_at','album','provider','profile_type','author_name','author_company','question_tr'], true);
                                ?>
                                <th>
                                    <?php if ($isSortable): ?>
                                        <?php
                                        $next = $sortCol === $colKey && strtoupper($sortDir) === 'DESC' ? 'asc' : 'desc';
                                        $q = current_query([$colKey => $colKey, 'yon' => $next, 'page' => '']);
                                        ?>
                                        <a href="<?= e(url($currentPath, $q)) ?>">
                                            <?= e($label) ?>
                                            <span class="table__sort" aria-hidden="true"><?= $sortCol === $colKey ? ($sortDir === 'DESC' ? '▼' : '▲') : '↕' ?></span>
                                        </a>
                                    <?php else: ?>
                                        <?= e($label) ?>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                            <th class="u-right"><?= e(t('admin.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $id = (int) $row['id']; $active = (int) ($row['is_active'] ?? 1) === 1; ?>
                        <tr class="table__row">
                            <td class="checkbox-cell">
                                <label class="checkbox">
                                    <input type="checkbox" name="ids[]" value="<?= e($id) ?>" data-bulk-item
                                           aria-label="<?= e((string) ($row['title_tr'] ?? $row['name'] ?? $row['author_name'] ?? $row['question_tr'] ?? '#' . $id)) ?>">
                                </label>
                            </td>

                            <?php foreach ($columns as $colKey => $col): ?>
                                <?php $val = $row[$colKey] ?? null; ?>
                                <td>
                                    <?php switch ((string) $col['type']):
                                        case 'image': ?>
                                            <?php $u = upload_url($val); ?>
                                            <?php if ($u): ?>
                                                <img src="<?= e($u) ?>" alt="" class="thumb thumb--<?= e((string) ($col['width'] ?? 48)) ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="u-faint" aria-hidden="true">—</span>
                                            <?php endif; ?>
                                            <?php break; ?>

                                        <?php case 'avatar': ?>
                                            <?php $u = upload_url($val); ?>
                                            <span class="avatar"><?php if ($u): ?><img src="<?= e($u) ?>" alt="" loading="lazy"><?php else: ?><?= e(strtoupper(mb_substr((string) ($row['name'] ?? $row['author_name'] ?? '?'), 0, 1, 'UTF-8'))) ?><?php endif; ?></span>
                                            <?php break; ?>

                                        <?php case 'title': ?>
                                            <a href="<?= e($editBase . $id) ?>" class="table__title"><?= e(str_limit((string) $val, 60)) ?><?php if (($row['slug'] ?? '') !== ''): ?><span class="table__slug">/<?= e($row['slug']) ?></span><?php endif; ?></a>
                                            <?php if (!empty($row['slug'])): ?><span class="table__slug">/<?= e($row['slug']) ?></span><?php endif; ?>
                                            <?php break; ?>

                                        <?php case 'date': ?>
                                            <?= e($val ? format_date((string) $val) : '—') ?>
                                            <?php break; ?>

                                        <?php case 'number': ?>
                                            <span class="u-right"><?= e(number_format((float) $val, 0, ',', '.')) ?></span>
                                            <?php break; ?>

                                        <?php case 'rating': ?>
                                            <span class="rating" aria-label="<?= e((int) $val) ?>/5"><?= e(str_repeat('★', max(0, min(5, (int) $val)))) ?></span>
                                            <?php break; ?>

                                        <?php case 'badge': ?>
                                            <span class="badge badge--muted"><?= e((int) $val === 1 ? t('admin.module_system') : t('admin.module_public')) ?></span>
                                            <?php break; ?>

                                        <?php case 'check': ?>
                                            <?= (int) $val === 1 ? '<span class="badge badge--success">✓</span>' : '<span class="u-faint">—</span>' ?>
                                            <?php break; ?>

                                        <?php case 'order': ?>
                                            <span class="u-mono"><?= e($val ?? '0') ?></span>
                                            <?php break; ?>

                                        <?php case 'toggle': ?>
                                            <button type="submit" name="islem" value="<?= $active ? 'pasif' : 'aktif' ?>"
                                                    formaction="<?= e($formUrl . '/' . $id) ?>"
                                                    class="badge <?= $active ? 'badge--success' : 'badge--muted' ?>"
                                                    data-state="<?= $active ? 'success' : '' ?>"
                                                    aria-label="<?= e($active ? t('admin.passive') : t('admin.active')) ?>">
                                                <?= $active ? t('admin.active') : t('admin.passive') ?>
                                            </button>
                                            <?php break; ?>

                                        default: ?>
                                            <?= e($val === null || $val === '' ? '—' : str_limit((string) $val, 48)) ?>
                                    <?php endswitch; ?>
                                </td>
                            <?php endforeach; ?>

                            <td class="u-right">
                                <div class="row-actions">
                                    <a href="<?= e($editBase . $id) ?>" class="btn btn--ghost btn--sm"><?= e(t('admin.edit_button')) ?></a>

                                    <?php if ((!empty($modelClass) && $modelClass::columnExists($modelClass::table(), 'is_featured'))): ?>
                                        <button type="submit" name="islem" value="ozellik"
                                                formaction="<?= e($formUrl . '/' . $id) ?>"
                                                class="btn btn--ghost btn--sm"
                                                title="<?= e(t('admin.featured_on')) ?>">★</button>
                                    <?php endif; ?>

                                    <?php if ((int) ($row['is_system'] ?? 0) !== 1): ?>
                                        <button type="submit" name="islem" value="sil" formaction="<?= e($formUrl . '/' . $id) ?>"
                                                class="btn btn--danger btn--sm"
                                                data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= e(t('admin.delete')) ?></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel__foot u-between">
                <span class="toolbar__count" data-bulk-count>0 <?= e(t('admin.bulk', ['n' => 0])) ?></span>
                <button type="submit" name="islem" value="sil-toplu" class="btn btn--danger btn--sm"
                        data-bulk-submit disabled
                        data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= e(t('admin.delete')) ?></button>
            </div>
        </div>
    </form>
<?php endif; ?>

<?= $paginator->links('admin.partials.pagination') ?>
