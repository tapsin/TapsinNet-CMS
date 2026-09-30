<?php declare(strict_types=1); ?>
<?php
/**
 * ANA SAYFA — Dala void layout.
 *
 * Hero: two-column asymmetric — headline + CTA left, constellation canvas right.
 * Subsequent sections: left-aligned oversized headline + body, whitespace-separated.
 */
$ctaText  = setting('home_cta_text', '') ?: t('home.start_project');
$cta2Text = setting('home_cta2_text', '') ?: t('nav.projects');

[$lede, $sideA, $sideB] = $feature;
?>

<!-- ══════════════ 1 · HERO — void canvas + constellation ══════════════ -->
<section class="hero" aria-labelledby="hero-title">
    <canvas class="hero__canvas" id="constellation" aria-hidden="true"></canvas>

    <div class="wrap hero__grid">
        <div class="hero__main">
            <?php if (($hero['eyebrow'] ?? '') !== ''): ?>
                <p class="hero__eyebrow"><?= e($hero['eyebrow']) ?></p>
            <?php endif; ?>

            <h1 class="hero__title" id="hero-title"><?= e($hero['title']) ?></h1>

            <?php if (($hero['text'] ?? '') !== ''): ?>
                <p class="hero__text"><?= e($hero['text']) ?></p>
            <?php endif; ?>

            <div class="hero__actions">
                <a href="<?= e(url('/iletisim')) ?>" class="btn btn--primary btn--lg"><?= e($ctaText) ?></a>
                <?php if (module('projects', 'active')): ?>
                    <a href="<?= e(module_url('projects')) ?>" class="hero__text-link"><?= e($cta2Text) ?> <span aria-hidden="true">↗</span></a>
                <?php endif; ?>
            </div>

            <?php if (!empty($stats)): ?>
                <dl class="hero__stats">
                    <?php foreach ($stats as $stat): ?>
                        <div class="hero__stat">
                            <dt class="u-visually-hidden"><?= e($stat['label']) ?></dt>
                            <dd>
                                <span class="hero__stat-value"><?= e(number_format((float) $stat['value'], 0, ',', '.')) ?></span>
                                <span class="hero__stat-label"><?= e($stat['label']) ?></span>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>

        <div class="hero__side" aria-hidden="true">
            <?php
            $objectImages = [
                ['src' => '/uploads/instagram/proje-01.jpg', 'post' => 'Dd0ZYsugiM9'],
                ['src' => '/uploads/instagram/proje-02.jpg', 'post' => 'Dd0RkxnAoqR'],
                ['src' => '/uploads/instagram/proje-03.jpg', 'post' => 'Ddvj_9MAhit'],
                ['src' => '/uploads/instagram/proje-04.jpg', 'post' => 'DdvjcYfAqOM'],
                ['src' => '/uploads/instagram/proje-05.jpg', 'post' => 'DdvjSwWgs9O'],
                ['src' => '/uploads/instagram/proje-06.jpg', 'post' => 'DdvjGlngpoK'],
            ];
            foreach ($objectImages as $objectIndex => $objectImage):
            ?>
                <figure class="hero-object hero-object--<?= e((string) ($objectIndex + 1)) ?>">
                    <a href="<?= e('https://www.instagram.com/p/' . $objectImage['post'] . '/') ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?= e(url($objectImage['src'])) ?>" alt="TapsinNet Instagram proje görseli" loading="lazy" decoding="async">
                    </a>
                </figure>
            <?php endforeach; ?>
            <span class="hero__orbit-label">TAPSIN / INSTAGRAM&nbsp; ↗</span>
        </div>
    </div>
</section>

<nav class="oryzo-rail" aria-label="Ana sayfa bölümleri" data-slide-rail>
    <span class="oryzo-rail__label">TAPSINNET / EDITORIAL</span>
    <ol>
        <li><a href="#hero-title" data-slide-link="0" aria-label="Giriş" aria-current="true"><span>01</span></a></li>
        <li><a href="#about-title" data-slide-link="1" aria-label="Hakkımda"><span>02</span></a></li>
        <li><a href="#showcase-title" data-slide-link="2" aria-label="Öne çıkan işler"><span>03</span></a></li>
        <li><a href="#services-title" data-slide-link="3" aria-label="Hizmetler"><span>04</span></a></li>
        <li><a href="#refs-title" data-slide-link="4" aria-label="Referanslar"><span>05</span></a></li>
        <li><a href="#cta-title" data-slide-link="5" aria-label="İletişim"><span>06</span></a></li>
    </ol>
</nav>

<?php /* ══════════════ 2 · HAKKIMDA — solo headline + body ══════════════ */ ?>
<?php if (trim((string) $about['text']) !== ''): ?>
    <section class="section band--tight" aria-labelledby="about-title">
        <div class="wrap">
            <div class="section__head">
                <h2 class="section__title" id="about-title"><?= e(t('home.about_title')) ?></h2>
                <div class="prose prose--tight"><?= safe_html($about['text'], false) ?></div>
                <?php if (($about['cv'] ?? '') !== ''): ?>
                    <a href="<?= e(url($about['cv'])) ?>" class="btn--quiet"><?= e(t('detail.download')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 3 · VİTRİN — asymmetric ══════════════ */ ?>
<?php if ($lede !== null): ?>
    <section class="band" aria-labelledby="showcase-title">
        <div class="wrap">
            <div class="band__head">
                <h2 class="band__title" id="showcase-title"><?= e(t('home.featured')) ?></h2>
                <a href="<?= e(module_url('projects')) ?>" class="band__more">
                    <?= e(t('home.view_all')) ?>
                    <span class="band__n"><?= e(number_format((int) $workCount, 0, ',', '.')) ?></span>
                </a>
            </div>

            <div class="showcase">
                <article class="showcase__lede">
                    <?php $cover = upload_url($lede['cover_image'] ?? null); ?>
                    <?php if ($cover): ?>
                        <a href="<?= e(url('/isler/' . $lede['slug'])) ?>" class="showcase__media" tabindex="-1" aria-hidden="true">
                            <img src="<?= e($cover) ?>" alt="" loading="eager" fetchpriority="high" decoding="async">
                        </a>
                    <?php endif; ?>
                    <div class="showcase__body">
                        <h3 class="showcase__title">
                            <a href="<?= e(url('/isler/' . $lede['slug'])) ?>"><?= e(loc($lede, 'title')) ?></a>
                        </h3>
                        <?php $sum = loc($lede, 'summary'); ?>
                        <?php if ($sum !== ''): ?>
                            <p class="showcase__text"><?= e(str_limit($sum, 190)) ?></p>
                        <?php endif; ?>
                        <p class="meta-list">
                            <?php if (!empty($lede['client'])): ?><span><?= e($lede['client']) ?></span><?php endif; ?>
                            <?php if (!empty($lede['category'])): ?><span><?= e($lede['category']) ?></span><?php endif; ?>
                            <?php if (!empty($lede['project_date'])): ?><span><?= e(format_date($lede['project_date'], 'Y')) ?></span><?php endif; ?>
                        </p>
                    </div>
                </article>

                <div class="showcase__side">
                    <?php foreach ([$sideA, $sideB] as $side):
                        if (empty($side)) { continue; } ?>
                        <article class="showcase__item">
                            <?php $c = upload_url($side['cover_image'] ?? null); ?>
                            <?php if ($c): ?>
                                <a href="<?= e(url('/isler/' . $side['slug'])) ?>" class="showcase__thumb" tabindex="-1" aria-hidden="true">
                                    <img src="<?= e($c) ?>" alt="" loading="lazy" decoding="async">
                                </a>
                            <?php endif; ?>
                            <h3 class="showcase__item-title">
                                <a href="<?= e(url('/isler/' . $side['slug'])) ?>"><?= e(loc($side, 'title')) ?></a>
                            </h3>
                            <p class="meta-list">
                                <?php if (!empty($side['client'])): ?><span><?= e($side['client']) ?></span><?php endif; ?>
                                <?php if (!empty($side['category'])): ?><span><?= e($side['category']) ?></span><?php endif; ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 4 · HİZMETLER — yükselen timeline kareleri ══════════════ */ ?>
<?php if ($services !== []): ?>
    <section class="band band--alt" aria-labelledby="services-title">
        <div class="wrap">
            <div class="band__head">
                <h2 class="band__title" id="services-title"><?= e(\Core\ModuleRegistry::name('services')) ?></h2>
                <a href="<?= e(module_url('services')) ?>" class="band__more"><?= e(t('home.view_all')) ?></a>
            </div>

            <ol class="service-timeline">
                <?php foreach ($services as $i => $s): ?>
                    <?php $serviceCover = upload_url($s['cover_image'] ?? null) ?: url('/assets/img/placeholder.svg'); ?>
                    <li class="service-timeline__item">
                        <article class="service-tile">
                            <a href="<?= e(url('/hizmetler/' . $s['slug'])) ?>" class="service-tile__media" aria-label="<?= e(loc($s, 'title')) ?>">
                                <img src="<?= e($serviceCover) ?>" alt="" loading="lazy" decoding="async">
                                <span class="service-tile__number"><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                            </a>
                            <div class="service-tile__body">
                                <div class="service-tile__meta">
                                    <time datetime="<?= e((string) ($s['created_at'] ?? '')) ?>"><?= e(!empty($s['created_at']) ? format_date((string) $s['created_at'], 'Y') : 'HİZMET') ?></time>
                                    <?php if (!empty($s['duration'])): ?><span><?= e($s['duration']) ?></span><?php endif; ?>
                                </div>
                                <h3 class="service-tile__title"><a href="<?= e(url('/hizmetler/' . $s['slug'])) ?>"><?= e(!empty($s['icon']) ? $s['icon'] . ' ' : '') ?><?= e(loc($s, 'title')) ?></a></h3>
                                <?php $ex = loc($s, 'excerpt'); ?>
                                <?php if ($ex !== ''): ?><p class="service-tile__text"><?= e(str_limit($ex, 110)) ?></p><?php endif; ?>
                            </div>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 5 · REFERANSLAR — quote typography ══════════════ */ ?>
<?php if ($testimonials !== []): ?>
    <section class="section" aria-labelledby="refs-title">
        <div class="wrap">
            <div class="section__head">
                <h2 class="section__title" id="refs-title"><?= e(\Core\ModuleRegistry::name('testimonials')) ?></h2>
            </div>

            <div class="testimonial-slider" data-testimonial-slider aria-label="Referanslar">
                <div class="testimonial-slider__track">
                    <?php foreach ($testimonials as $index => $q): ?>
                        <?php $authorName = trim((string) ($q['author_name'] ?? '')); $parts = preg_split('/\s+/', $authorName); $initials = mb_strtoupper(mb_substr((string) ($parts[0] ?? '?'), 0, 1, 'UTF-8') . mb_substr((string) ($parts[count($parts) - 1] ?? ''), 0, 1, 'UTF-8'), 'UTF-8'); ?>
                        <figure class="testimonial-slide<?= $index === 0 ? ' is-active' : '' ?>" data-testimonial-slide="<?= e((string) $index) ?>">
                            <div class="testimonial-slide__avatar">
                                <?php if (!empty($q['author_avatar'])): ?><img src="<?= e(upload_url($q['author_avatar'])) ?>" alt="<?= e($authorName) ?>" loading="lazy" decoding="async"><?php else: ?><span><?= e($initials) ?></span><?php endif; ?>
                            </div>
                            <div class="testimonial-slide__content">
                                <blockquote>“<?= e(loc($q, 'quote')) ?>”</blockquote>
                                <figcaption>
                                    <strong><?= e($authorName) ?></strong>
                                    <?php $role = trim((string) ($q['author_title'] ?? '') . ' · ' . (string) ($q['author_company'] ?? ''), ' ·'); ?>
                                    <?php if ($role !== ''): ?><span><?= e($role) ?></span><?php endif; ?>
                                </figcaption>
                            </div>
                        </figure>
                    <?php endforeach; ?>
                </div>
                <div class="testimonial-slider__nav" aria-label="Referans seçimi">
                    <?php foreach ($testimonials as $index => $q): ?><button type="button" class="testimonial-dot<?= $index === 0 ? ' is-active' : '' ?>" data-testimonial-dot="<?= e((string) $index) ?>" aria-label="<?= e((string) ($index + 1)) ?>. referans" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"></button><?php endforeach; ?>
                </div>
            </div>
            <a href="<?= e(module_url('testimonials')) ?>" class="btn btn--ghost testimonials__all"><?= e(t('home.view_all')) ?> <span aria-hidden="true">↗</span></a>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 6 · HABER + SSS — two-col ══════════════ */ ?>
<?php if ($news !== [] || $faq !== []): ?>
    <section class="band" aria-labelledby="news-title">
        <div class="wrap split">
            <?php if ($news !== []): ?>
                <div class="split__col">
                    <h2 class="band__title" id="news-title"><?= e(\Core\ModuleRegistry::name('news')) ?></h2>
                    <ul class="line-list line-list--compact">
                        <?php foreach ($news as $n): ?>
                            <li class="line-list__item">
                                <span class="line-list__main">
                                    <a href="<?= e(url('/haberler/' . $n['slug'])) ?>" class="line-list__title"><?= e(loc($n, 'title')) ?></a>
                                    <span class="line-list__text"><?= e(time_ago($n['published_at'] ?? $n['created_at'])) ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= e(module_url('news')) ?>" class="btn--quiet"><?= e(t('home.view_all')) ?></a>
                </div>
            <?php endif; ?>

            <?php if ($faq !== []): ?>
                <div class="split__col">
                    <h2 class="band__title" id="faq-title"><?= e(\Core\ModuleRegistry::name('faq')) ?></h2>
                    <?php foreach ($faq as $f): ?>
                        <details class="faq-item">
                            <summary><?= e(loc($f, 'question')) ?></summary>
                            <div class="faq-item__answer"><?= safe_html(loc($f, 'answer'), false) ?></div>
                        </details>
                    <?php endforeach; ?>
                    <a href="<?= e(module_url('faq')) ?>" class="btn--quiet"><?= e(t('home.view_all')) ?></a>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 7 · ÇAĞRI ══════════════ */ ?>
<section class="cta" aria-labelledby="cta-title">
    <div class="wrap cta__inner">
        <div>
            <h2 class="cta__title" id="cta-title"><?= e(t('home.cta_title')) ?></h2>
            <p class="cta__text"><?= e(t('home.cta_text')) ?></p>
        </div>
        <a href="<?= e(url('/iletisim')) ?>" class="btn btn--primary btn--lg"><?= e($ctaText) ?></a>
    </div>
</section>
