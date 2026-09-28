<?php declare(strict_types=1); ?>
<?php
/**
 * ANA SAYFA — odaklı portfolyo.
 *
 * Beş bölüm, beşi farklı ağırlıkta. Önceki yapıda 10 eşit bölüm vardı ve
 * sayfa "düz bant" gibi okunuyordu; gözün nereye gideceği belli değildi.
 *
 *   1. Vitrin       → asimetrik: 1 büyük iş + 2 yanında
 *   2. Hizmetler    → ince ayraçlı tek satır liste
 *   3. Referanslar  → yalnızca tipografi
 *   4. Haber + SSS  → iki kolon
 *   5. Ekip çağrısı
 */
$ctaText  = setting('home_cta_text', '') ?: t('home.start_project');
$cta2Text = setting('home_cta2_text', '') ?: t('nav.projects');

[$lede, $sideA, $sideB] = $feature;
?>

<!-- ══════════════ 1 · HERO ══════════════ -->
<section class="hero" aria-labelledby="hero-title">
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
                    <a href="<?= e(module_url('projects')) ?>" class="btn btn--ghost btn--lg"><?= e($cta2Text) ?></a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($stats)): ?>
            <aside class="hero__side">
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
            </aside>
        <?php endif; ?>
    </div>
</section>

<?php /* ══════════════ 2 · HAKKIMDA — dar kolon ══════════════ */ ?>
<?php if (trim((string) $about['text']) !== ''): ?>
    <div class="band band--tight">
        <div class="wrap">
            <div class="band__col">
                <h2 class="section__eyebrow"><?= e(t('home.about_title')) ?></h2>
                <div class="prose prose--tight"><?= safe_html($about['text'], false) ?></div>
                <?php if (($about['cv'] ?? '') !== ''): ?>
                    <a href="<?= e(url($about['cv'])) ?>" class="btn--quiet"><?= e(t('detail.download')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php /* ══════════════ 3 · VİTRİN — asimetrik ══════════════ */ ?>
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

<?php /* ══════════════ 4 · HİZMETLER — ince ayraçlı liste ══════════════ */ ?>
<?php if ($services !== []): ?>
    <section class="band band--alt" aria-labelledby="services-title">
        <div class="wrap">
            <div class="band__head">
                <h2 class="band__title" id="services-title"><?= e(\Core\ModuleRegistry::name('services')) ?></h2>
                <a href="<?= e(module_url('services')) ?>" class="band__more"><?= e(t('home.view_all')) ?></a>
            </div>

            <ul class="line-list">
                <?php foreach ($services as $i => $s): ?>
                    <li class="line-list__item">
                        <span class="line-list__idx" aria-hidden="true"><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                        <span class="line-list__main">
                            <a href="<?= e(url('/hizmetler/' . $s['slug'])) ?>" class="line-list__title">
                                <?= e(!empty($s['icon']) ? $s['icon'] . ' ' : '') ?><?= e(loc($s, 'title')) ?>
                            </a>
                            <?php $ex = loc($s, 'excerpt'); ?>
                            <?php if ($ex !== ''): ?>
                                <span class="line-list__text"><?= e(str_limit($ex, 96)) ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($s['duration'])): ?>
                            <span class="line-list__aside"><?= e($s['duration']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 5 · REFERANSLAR — yalnızca tipografi ══════════════ */ ?>
<?php if ($testimonials !== []): ?>
    <section class="band band--inset" aria-labelledby="refs-title">
        <div class="wrap">
            <div class="band__head">
                <h2 class="band__title" id="refs-title"><?= e(\Core\ModuleRegistry::name('testimonials')) ?></h2>
                <a href="<?= e(module_url('testimonials')) ?>" class="band__more"><?= e(t('home.view_all')) ?></a>
            </div>

            <div class="quote-grid">
                <?php foreach ($testimonials as $q): ?>
                    <figure class="quote">
                        <blockquote><?= e(loc($q, 'quote')) ?></blockquote>
                        <figcaption>
                            <strong><?= e($q['author_name']) ?></strong>
                            <?php $role = trim((string) ($q['author_title'] ?? '') . ' · ' . (string) ($q['author_company'] ?? ''), ' ·'); ?>
                            <?php if ($role !== ''): ?><span><?= e($role) ?></span><?php endif; ?>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php /* ══════════════ 6 · HABER + SSS — iki kolon ══════════════ */ ?>
<?php if ($news !== [] || $faq !== []): ?>
    <section class="band">
        <div class="wrap split">
            <?php if ($news !== []): ?>
                <div class="split__col">
                    <h2 class="band__title"><?= e(\Core\ModuleRegistry::name('news')) ?></h2>
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
                    <h2 class="band__title"><?= e(\Core\ModuleRegistry::name('faq')) ?></h2>
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
