<?php declare(strict_types=1); ?>
<?php
/** STATİK SAYFA — KVKK, gizlilik, çerez, hakkında… */
$title = loc($row, 'title');
?>
<section class="section section--tight">
    <div class="wrap wrap--narrow">
        <nav class="breadcrumb" aria-label="<?= e(t('pagination.page')) ?>">
            <span class="breadcrumb__item"><a href="<?= e(url('/')) ?>"><?= e(t('nav.home')) ?></a></span>
            <span class="breadcrumb__item"><?= e($title) ?></span>
        </nav>

        <h1 class="section__title"><?= e($title) ?></h1>

        <?php $cover = $row['cover_image'] ?? null; ?>
        <?php if (!empty($cover)): ?>
            <figure class="media media__ratio media__ratio--wide">
                <img src="<?= e(upload_url($cover)) ?>" alt="" class="media__img" loading="lazy" decoding="async">
            </figure>
        <?php endif; ?>

        <?php $excerpt = loc($row, 'excerpt'); ?>
        <?php if ($excerpt !== ''): ?>
            <p class="lead"><?= e($excerpt) ?></p>
        <?php endif; ?>

        <div class="prose"><?= safe_html(loc($row, 'body')) ?></div>

        <div class="form-actions">
            <a href="<?= e(url('/iletisim')) ?>" class="btn btn--primary"><?= e(t('nav.contact')) ?></a>
        </div>
    </div>
</section>
