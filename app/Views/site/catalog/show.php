<?php declare(strict_types=1); ?>
<?php
/**
 * KATALOG DETAY — tüm içerik modüllerinin ortak detay şablonu.
 * Gövde, modüle göre değişir; çerçeve (breadcrumb, meta, gezinme, yorumlar) ortaktır.
 */

use Models\Project;

$title    = loc($row, 'title') ?: ($row['name'] ?? $row['author_name'] ?? $row['question_tr'] ?? $row['caption_tr'] ?? '');
$cover    = $row['cover_image'] ?? $row['image_path'] ?? $row['avatar'] ?? $row['author_avatar'] ?? $row['media_path'] ?? null;
$excerpt  = loc($row, 'summary') ?: loc($row, 'excerpt') ?: loc($row, 'description') ?: loc($row, 'caption') ?: '';
$bodyHtml = loc($row, 'body') ?: loc($row, 'answer') ?: loc($row, 'bio') ?: loc($row, 'quote') ?: '';
$listUrl  = module_url($module);
$listName = $moduleName;
?>

<section class="section section--tight">
    <div class="wrap">

        <nav class="breadcrumb" aria-label="<?= e(t('pagination.page')) ?>">
            <span class="breadcrumb__item"><a href="<?= e(url('/')) ?>"><?= e(t('nav.home')) ?></a></span>
            <?php if (!empty($listUrl)): ?>
                <span class="breadcrumb__item"><a href="<?= e($listUrl) ?>"><?= e($listName) ?></a></span>
            <?php endif; ?>
            <span class="breadcrumb__item"><?= e(str_limit($title, 48)) ?></span>
        </nav>

        <div class="u-between">
            <h1 class="section__title"><?= e($title) ?></h1>
            <?php if ($canComment): ?>
                <a href="#comments" class="btn--quiet">
                    <?= e(number_format((int) $commentCount, 0, ',', '.')) ?> <?= e(t('comment.title')) ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- ══════════════ MODÜLE GÖRE GÖVDE ══════════════ -->

        <?php if ($module === 'projects'): ?>
            <div class="meta-list">
                <?php if (!empty($row['client'])): ?>
                    <span><?= e(t('detail.client')) ?>: <strong><?= e($row['client']) ?></strong></span>
                <?php endif; ?>
                <?php if (!empty($row['category'])): ?>
                    <span><?= e(t('detail.category')) ?>: <?= e($row['category']) ?></span>
                <?php endif; ?>
                <?php if (!empty($row['project_date'])): ?>
                    <span><?= e(t('detail.date')) ?>: <?= e(format_date($row['project_date'], 'Y')) ?></span>
                <?php endif; ?>
                <?php if (!empty($row['location'])): ?>
                    <span><?= e(t('detail.location')) ?>: <?= e($row['location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($row['duration'])): ?>
                    <span><?= e(t('detail.duration')) ?>: <?= e($row['duration']) ?></span>
                <?php endif; ?>
                <span><?= e(number_format((int) ($row['views'] ?? 0), 0, ',', '.')) ?> <?= e(t('detail.views')) ?></span>
            </div>

        <?php elseif ($module === 'news'): ?>
            <div class="meta-list">
                <time datetime="<?= e($row['published_at'] ?? $row['created_at']) ?>">
                    <?= e(format_date($row['published_at'] ?? $row['created_at'])) ?>
                </time>
                <?php if (!empty($row['author'])): ?><span><?= e($row['author']) ?></span><?php endif; ?>
                <?php if (!empty($row['read_minutes'])): ?><span><?= e((int) $row['read_minutes']) ?> dk</span><?php endif; ?>
                <span><?= e(number_format((int) ($row['views'] ?? 0), 0, ',', '.')) ?> <?= e(t('detail.views')) ?></span>
            </div>

        <?php elseif ($module === 'videos'): ?>
            <div class="video-frame">
                <?php if ($cover): ?>
                    <img src="<?= e(upload_url($cover)) ?>" alt="" class="media__img" loading="lazy" decoding="async">
                <?php endif; ?>
                <button type="button" class="video-frame__play"
                        data-video-embed="<?= e(video_embed_url($row)) ?>"
                        data-video-title="<?= e($title) ?>"
                        aria-label="<?= e(t('detail.watch')) ?>">
                    <span aria-hidden="true">▶</span>
                </button>
            </div>

        <?php elseif ($module === 'certificates'): ?>
            <dl class="dl">
                <?php if (!empty($row['issuer'] ?? $row['issuer_tr'])): ?>
                    <div class="dl__row">
                        <dt class="dl__key"><?= e(t('detail.issuer')) ?></dt>
                        <dd class="dl__value"><?= e(loc($row, 'issuer') ?: ($row['issuer'] ?? '')) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['issue_date'])): ?>
                    <div class="dl__row">
                        <dt class="dl__key"><?= e(t('contact.field.subject')) ?></dt>
                        <dd class="dl__value"><?= e(format_date($row['issue_date'])) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['expiry_date'])): ?>
                    <div class="dl__row">
                        <dt class="dl__key"><?= e(t('admin.settings.home')) ?></dt>
                        <dd class="dl__value"><?= e(format_date($row['expiry_date'])) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['credential_id'])): ?>
                    <div class="dl__row">
                        <dt class="dl__key"><?= e(t('detail.credential')) ?></dt>
                        <dd class="dl__value u-mono"><?= e($row['credential_id']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['score'])): ?>
                    <div class="dl__row">
                        <dt class="dl__key"><?= e(t('detail.score')) ?></dt>
                        <dd class="dl__value"><?= e($row['score']) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>

        <?php elseif ($module === 'profiles'): ?>
            <div class="avatar avatar--xl" aria-hidden="true">
                <?php if (!empty($row['avatar'])): ?>
                    <img src="<?= e(upload_url($row['avatar'])) ?>" alt="" loading="lazy" decoding="async">
                <?php else: ?>
                    <?= e(strtoupper(mb_substr((string) ($row['name'] ?? '?'), 0, 1, 'UTF-8'))) ?>
                <?php endif; ?>
            </div>
            <p class="lead"><?= e(loc($row, 'title')) ?></p>
            <?php $socials = \Models\Profile::socials($row); ?>
            <?php if ($socials !== []): ?>
                <div class="btn-row">
                    <?php foreach ($socials as $net => $href): ?>
                        <a href="<?= e($href) ?>" class="tag" rel="noopener noreferrer nofollow" target="_blank"><?= e(ucfirst($net)) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($module === 'testimonials'): ?>
            <p class="rating" aria-label="<?= e((int) ($row['rating'] ?? 5)) ?> / 5">
                <?= e(str_repeat('★', max(0, min(5, (int) ($row['rating'] ?? 5))))) ?>
            </p>
            <blockquote class="pullquote"><?= e(loc($row, 'quote')) ?></blockquote>
            <p class="u-muted">
                <strong><?= e($row['author_name']) ?></strong>
                <?php if (!empty($row['author_title'])): ?><?= e($row['author_title']) ?><?php endif; ?>
                <?php if (!empty($row['author_company'])): ?>· <?= e($row['author_company']) ?><?php endif; ?>
            </p>

        <?php elseif ($module === 'faq'): ?>
            <div class="prose"><?= safe_html($bodyHtml) ?></div>

        <?php elseif ($module === 'services'): ?>
            <?php $items = \Models\Service::deliverables($row); ?>
            <?php if ($items !== []): ?>
                <h2 class="section__eyebrow"><?= e(t('home.view')) ?></h2>
                <ul class="service-card__list">
                    <?php foreach ($items as $item): ?>
                        <li><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php elseif ($module === 'gallery'): ?>
            <figure class="media media__ratio" data-lightbox-src="<?= e(upload_url($row['image_path'])) ?>"
                    data-lightbox-caption="<?= e($title) ?>">
                <img src="<?= e(upload_url($row['image_path'])) ?>"
                     alt="<?= e($row['alt_text'] ?: $title) ?>"
                     class="media__img" loading="lazy" decoding="async">
            </figure>

        <?php elseif ($module === 'social'): ?>
            <p><?= e(loc($row, 'caption')) ?></p>
            <a href="<?= e($row['permalink']) ?>" class="btn btn--ghost" rel="noopener noreferrer nofollow" target="_blank">
                <?= e(t('social.view_post')) ?>
            </a>
        <?php endif; ?>

        <!-- ══════════════ ORTAK ALT KISIM ══════════════ -->

        <?php if ($excerpt !== '' && $module !== 'testimonials' && $module !== 'faq'): ?>
            <p class="lead"><?= e($excerpt) ?></p>
        <?php endif; ?>

        <?php if ($bodyHtml !== '' && !in_array($module, ['faq', 'gallery', 'social'], true)): ?>
            <div class="prose"><?= safe_html($bodyHtml) ?></div>
        <?php endif; ?>

        <!-- Proje galerisi -->
        <?php if ($module === 'projects'):
            $shots = \Models\Project::gallery($row);
            if ($shots !== []): ?>
                <h2 class="section__eyebrow"><?= e(t('detail.gallery')) ?></h2>
                <div class="card-grid card-grid--3">
                    <?php foreach ($shots as $i => $shot): ?>
                        <figure class="media media__ratio media__ratio--wide"
                                data-lightbox-src="<?= e(upload_url($shot)) ?>"
                                data-lightbox-caption="<?= e($title) ?>">
                            <img src="<?= e(upload_url($shot)) ?>" alt="<?= e($title) ?> — <?= e($i + 1) ?>"
                                 class="media__img" loading="lazy" decoding="async">
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $stack = \Models\Project::tagList($row['tech_stack'] ?? null); ?>
            <?php if ($stack !== []): ?>
                <h2 class="section__eyebrow"><?= e(t('detail.tech')) ?></h2>
                <div class="tag-list">
                    <?php foreach ($stack as $tech): ?><span class="tag"><?= e($tech) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php $tags = \Models\Project::tagList($row['tags'] ?? null); ?>
            <?php if ($tags !== []): ?>
                <h2 class="section__eyebrow"><?= e(t('detail.tags')) ?></h2>
                <div class="tag-list">
                    <?php foreach ($tags as $tag): ?><span class="tag"><?= e($tag) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($row['project_url'])): ?>
                <div class="btn-row">
                    <a href="<?= e($row['project_url']) ?>" class="btn btn--ghost" rel="noopener noreferrer nofollow" target="_blank">
                        <?= e(t('app.read_more')) ?> ↗
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif ($module === 'certificates' && !empty($row['file_path'])): ?>
            <div class="btn-row">
                <a href="<?= e(upload_url($row['file_path'])) ?>" class="btn btn--ghost" download>
                    <?= e(t('detail.download')) ?>
                </a>
                <?php if (!empty($row['url'])): ?>
                    <a href="<?= e($row['url']) ?>" class="btn--quiet" rel="noopener noreferrer nofollow" target="_blank">
                        <?= e(t('detail.verify')) ?> ↗
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Önceki / sonraki -->
        <?php
        // KÖK NEDEN (düzeltildi): $currentPath detay yolunun KENDİSİDİR
        // (ör. /hizmetler/sizma-testi-12). Üstüne slug eklenince iki
        // segmentli (/hizmetler/a/b) bir yol üretiliyordu; bu rota yok,
        // 404 dönüyordu. Doğru adres modül rotası + slug olmalı.
        // Not: module_url()'in 2. parametresi slug değil, sorgu dizesidir.
        $modRoute  = (string) (\Core\ModuleRegistry::route($module) ?? $module);
        $langNow   = \Core\Translator::lang();
        $langPre   = $langNow === (string) \Core\Config::get('i18n.default') ? '' : $langNow . '/';
        $detailBase = '/' . $langPre . $modRoute;
        ?>
        <?php if ($prev !== null || $next !== null): ?>
            <nav class="u-between hairline-stack" aria-label="<?= e(t('detail.prev_next')) ?>">
                <?php if ($prev !== null): ?>
                    <a href="<?= e(url($detailBase . '/' . $prev['slug'])) ?>" class="btn--quiet">← <?= e(str_limit(loc($prev, 'title') ?: ($prev['name'] ?? ''), 40)) ?></a>
                <?php else: ?><span></span><?php endif; ?>
                <?php if ($next !== null): ?>
                    <a href="<?= e(url($detailBase . '/' . $next['slug'])) ?>" class="btn--quiet"><?= e(str_limit(loc($next, 'title') ?: ($next['name'] ?? ''), 40)) ?> →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <!-- İlgili içerikler -->
        <?php if (!empty($related)): ?>
            <section class="section section--flush-top">
                <div class="section__head">
                    <h2 class="section__title"><?= e(t('detail.related')) ?></h2>
                    <a href="<?= e((string) $listUrl) ?>" class="btn--quiet"><?= e(t('home.view_all')) ?></a>
                </div>
                <div class="card-grid card-grid--3">
                    <?php foreach ($related as $rel): ?>
                        <?= card_for($module, $rel) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Yorumlar -->
        <?php if ($canComment): ?>
            <?= partial('site.partials.comment.section', [
                'module'       => $module,
                'commentType'  => $commentType,
                'entityId'     => (int) $row['id'],
                'comments'     => $comments,
                'commentCount' => $commentCount,
                'returnUrl'    => $currentPath . '/' . ($row['slug'] ?? ''),
            ]) ?>
        <?php endif; ?>

    </div>
</section>
