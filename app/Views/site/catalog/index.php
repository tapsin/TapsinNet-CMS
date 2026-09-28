<?php declare(strict_types=1); ?>
<?php
/**
 * KATALOG LİSTELEME — tüm içerik modüllerinin ortak listeleme şablonu.
 * Route, config/modules.php'den üretildiği için tek dosya tüm modüllere hizmet eder.
 */

$searchable = in_array($module, ['projects', 'news', 'services', 'certificates', 'gallery', 'videos', 'profiles', 'faq'], true);
$gridWide   = in_array($module, ['projects', 'news', 'videos', 'gallery'], true);
$isList     = in_array($module, ['services', 'testimonials', 'faq'], true);
?>

<section class="section section--tight">
    <div class="wrap">

        <!-- ───────── Başlık ───────── -->
        <div class="section__head section__head--simple">
            <h1 class="section__title">
                <span class="rail__glyph" aria-hidden="true"><?= e($glyph) ?></span>
                <?= e($moduleName) ?>
            </h1>
            <?php if ($moduleDesc !== ''): ?>
                <p class="section__text"><?= e($moduleDesc) ?></p>
            <?php endif; ?>
        </div>

        <!-- ───────── Arama + filtreler ───────── -->
        <?php if ($searchable || !empty($filters)): ?>
            <div class="filters" role="search">
                <?php if ($searchable): ?>
                    <form class="searchbar" method="get" action="<?= e(url('/' . ltrim($currentPath, '/'))) ?>">
                        <?php
                        // Mevcut filtreleri koru
                        foreach ($filters as $fkey => $fdef):
                            $keep = (string) (current_query()[$fkey] ?? '');
                            if ($keep !== ''): ?>
                                <input type="hidden" name="<?= e($fkey) ?>" value="<?= e($keep) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <label class="visually-hidden" for="q"><?= e(t('listing.search_in')) ?></label>
                        <input type="search" id="q" name="q" class="input"
                               value="<?= e($term) ?>"
                               placeholder="<?= e(t('listing.search_ph')) ?>"
                               autocomplete="off">
                        <button type="submit" class="searchbar__btn" aria-label="<?= e(t('search.submit')) ?>">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M11 11l4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>

                <?php foreach ($filters as $fkey => $fdef):
                    $active = (string) (current_query()[$fkey] ?? '');
                    $opts   = $fdef['options'] ?? [];
                    if ($opts === []) { continue; }

                    // Yalnızca aktif olan seçenekte bir kayıt varsa gruba gizleme yok
                    $query = current_query();
                    $query[$fkey] = '';
                    $query = array_filter($query, static fn ($v) => $v !== '' && $v !== null);
                    $allUrl = url($currentPath, $query ?: []);
                    ?>
                    <div class="filters__group">
                        <span class="filters__label" id="flt-<?= e($fkey) ?>"><?= e($fdef['label']) ?></span>
                        <a href="<?= e($allUrl) ?>" class="filter-chip <?= $active === '' ? 'is-active' : '' ?>">
                            <?= e(t('filters.all')) ?>
                        </a>
                        <?php foreach ($opts as $opt):
                            $key = (string) ($opt['key'] ?? '');
                            $q2  = current_query();
                            $q2 = current_query([$fkey => $key]);
                            $chipUrl = url($currentPath, $q2);
                            ?>
                            <a href="<?= e($chipUrl) ?>" class="filter-chip <?= $active === $key ? 'is-active' : '' ?>">
                                <?= e($opt['label'] ?? $key) ?>
                                <?php if (isset($opt['count'])): ?>
                                    <span class="filter-chip__n"><?= e(number_format((int) $opt['count'], 0, ',', '.')) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ───────── Sonuç sayısı + sıralama ───────── -->
        <div class="u-between">
            <p class="result-count">
                <?= e(t('listing.showing', [
                    'from'  => $paginator->firstItem(),
                    'to'    => $paginator->lastItem(),
                    'total' => number_format($paginator->total, 0, ',', '.'),
                ])) ?>
            </p>
            <?php if ($activeFilter !== null): ?>
                <a href="<?= e(url($currentPath, current_query(['kategori' => '', 'album' => '', 'type' => '', 'provider' => '', 'yil' => '']))) ?>"
                   class="btn--quiet"><?= e(t('listing.clear_filters')) ?></a>
            <?php endif; ?>
        </div>

        <!-- ───────── Liste ───────── -->
        <?php if ($paginator->isEmpty()): ?>
            <div class="empty-state">
                <div class="empty-state__glyph" aria-hidden="true">◇</div>
                <h2 class="empty-state__title"><?= e(t('listing.empty')) ?></h2>
                <p class="empty-state__text"><?= e(t('listing.empty_hint')) ?></p>
                <?php if ($term !== '' || $activeFilter !== null): ?>
                    <div class="btn-row">
                        <a href="<?= e(url($currentPath)) ?>" class="btn btn--ghost"><?= e(t('listing.clear_filters')) ?></a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="<?= $isList ? 'u-stack' : 'card-grid card-grid--3' ?>">
                <?php foreach ($rows as $row): ?>
                    <?= card_for($module, $row, ['glyph' => $glyph]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ───────── Sayfalama ───────── -->
        <?= $paginator->links('site.partials.pagination') ?>

    </div>
</section>
