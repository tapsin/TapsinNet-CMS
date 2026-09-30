<?php declare(strict_types=1); ?>
<?php
/**
 * KATALOG LİSTELEME — tüm içerik modüllerinin ortak listeleme şablonu.
 * Route, config/modules.php'den üretildiği için tek dosya tüm modüllere hizmet eder.
 */

$searchable = in_array($module, ['projects', 'news', 'services', 'certificates', 'gallery', 'videos', 'profiles', 'faq'], true);
$gridWide   = in_array($module, ['projects', 'news', 'videos', 'gallery'], true);
$isList     = in_array($module, ['services', 'testimonials', 'faq'], true);
$isServices = $module === 'services';
$isProjects = $module === 'projects';
$isCertificates = $module === 'certificates';
$isGallery = $module === 'gallery';
$watermark = $isGallery ? upload_url(setting('gallery_watermark', '')) : null;
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
            <?php if ($isGallery): ?>
                <div class="gallery-mosaic">
                    <?php foreach ($rows as $index => $row): ?>
                        <?php
                            $galleryImage = upload_url($row['image_path'] ?? null);
                            if (!$galleryImage) { continue; }
                            $galleryTitle = loc($row, 'title') ?: (string) ($row['alt_text'] ?? 'Galeri görseli');
                            $galleryCaption = loc($row, 'caption') ?: '';
                            $galleryDate = (string) ($row['created_at'] ?? '');
                            $galleryMeta = trim($galleryTitle . ($galleryCaption !== '' ? ' — ' . $galleryCaption : ''), ' —');
                        ?>
                        <figure class="gallery-mosaic__item">
                            <a href="<?= e($galleryImage) ?>" class="gallery-mosaic__link" data-lightbox-src="<?= e($galleryImage) ?>" data-lightbox-caption="<?= e($galleryMeta . ($galleryDate !== '' ? ' · ' . format_date($galleryDate) : '')) ?>">
                                <img src="<?= e($galleryImage) ?>" alt="<?= e($row['alt_text'] ?? $galleryTitle) ?>" loading="lazy" decoding="async">
                                <?php if ($watermark): ?><img class="gallery-mosaic__watermark" src="<?= e($watermark) ?>" alt="" aria-hidden="true"><?php endif; ?>
                                <span class="gallery-mosaic__shade" aria-hidden="true"></span>
                                <span class="gallery-mosaic__zoom" aria-hidden="true">↗</span>
                            </a>
                            <figcaption class="gallery-mosaic__caption">
                                <strong><?= e($galleryTitle) ?></strong>
                                <?php if ($galleryDate !== ''): ?><time datetime="<?= e($galleryDate) ?>"><?= e(format_date($galleryDate)) ?></time><?php endif; ?>
                                <?php if ($galleryCaption !== ''): ?><span><?= e($galleryCaption) ?></span><?php endif; ?>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($isServices || $isProjects || $isCertificates): ?>
                <ol class="catalog-service-timeline catalog-service-timeline--<?= e($module) ?>">
                    <?php foreach ($rows as $index => $row): ?>
                        <?php
                            $serviceTitle = loc($row, 'title') ?: '';
                            $serviceSummary = loc($row, 'summary') ?: '';
                            $detailUrl = $isServices ? ($row['slug'] ? url('/hizmetler/' . $row['slug']) : '#') : ($isProjects ? ($row['slug'] ? url('/isler/' . $row['slug']) : '#') : (item_url('certificates', $row) ?: '#'));
                            $serviceCover = upload_url($row['cover_image'] ?? null) ?: url('/assets/img/placeholder.svg');
                            $displayDate = $isProjects ? ($row['completed_at'] ?? $row['project_date'] ?? $row['created_at'] ?? '') : ($isCertificates ? ($row['issue_date'] ?? $row['created_at'] ?? '') : ($row['created_at'] ?? ''));
                            $displayLabel = $isProjects ? trim((string) ($row['client'] ?? '') . ' · ' . (string) ($row['category'] ?? ''), ' ·') : ($isCertificates ? (loc($row, 'issuer') ?: '') : '');
                        ?>
                        <li class="catalog-service-timeline__item">
                            <article class="catalog-service-tile">
                                <a class="catalog-service-tile__media" href="<?= e($detailUrl) ?>" aria-label="<?= e($serviceTitle) ?>">
                                    <img src="<?= e($serviceCover) ?>" alt="" loading="lazy" decoding="async">
                                    <span class="catalog-service-tile__number"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                                </a>
                                <div class="catalog-service-tile__body">
                                    <div class="catalog-service-tile__meta">
                                        <time datetime="<?= e((string) $displayDate) ?>"><?= e($displayDate !== '' ? format_date((string) $displayDate, 'Y') : strtoupper($module)) ?></time>
                                        <?php if ($displayLabel !== ''): ?><span><?= e($displayLabel) ?></span><?php endif; ?>
                                    </div>
                                    <h2 class="catalog-service-tile__title"><a href="<?= e($detailUrl) ?>"><?= e($serviceTitle) ?></a></h2>
                                    <?php if ($serviceSummary !== ''): ?><p class="catalog-service-tile__text"><?= e(str_limit($serviceSummary, 160)) ?></p><?php endif; ?>
                                    <a class="catalog-service-tile__link" href="<?= e($detailUrl) ?>"><?= e(t('home.view')) ?> <span aria-hidden="true">↗</span></a>
                                </div>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <div class="<?= $isList ? 'u-stack' : 'card-grid card-grid--3' ?>">
                    <?php foreach ($rows as $row): ?>
                        <?= card_for($module, $row, ['glyph' => $glyph]) ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- ───────── Sayfalama ───────── -->
        <?= $paginator->links('site.partials.pagination') ?>

    </div>
</section>
