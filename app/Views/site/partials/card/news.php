<?php declare(strict_types=1); ?>
<?php
    $title = loc($row, 'title') ?: '';
    $excerpt = loc($row, 'excerpt') ?: '';
    $category = $row['category'] ?? '';
    $published = $row['published_at'] ?? $row['created_at'] ?? '';
    $views = $row['views'] ?? 0;
    $cover = $row['cover_image'] ?? null;
    $url = $row['slug'] ? url('/haberler/' . $row['slug']) : '#';
    $glyph = $glyph ?? (module('news', 'glyph') ?? '◆');
?>
<article class="card news-card">
    <div class="card__media">
        <?php if ($cover): ?>
            <img src="<?= e(upload_url($cover)) ?>" alt=""
                 loading="lazy" decoding="async"
                 class="media__img">
        <?php else: ?>
            <?= partial('site.partials.card._no-image', ['glyph' => $glyph]) ?>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <div class="card__meta">
            <?php if ($category !== ''): ?>
                <span class="tag tag--outline"><?= e($category) ?></span>
            <?php endif; ?>
            <?php if ($published !== ''): ?>
                <time datetime="<?= e($published) ?>"><?= e(format_date($published)) ?></time>
            <?php endif; ?>
            <?php if ($views > 0): ?>
                <span><?= e(number_format($views, 0, ',', '.')) ?> <?= t('detail.views') ?></span>
            <?php endif; ?>
        </div>
        <h3 class="card__title">
            <a href="<?= e($url) ?>"><?= e($title) ?></a>
        </h3>
        <?php if ($excerpt !== ''): ?>
            <p class="card__text"><?= e(str_limit($excerpt, 120)) ?></p>
        <?php endif; ?>
    </div>
    <footer class="card__foot">
        <a href="<?= e($url) ?>" class="btn btn--quiet"><?= t('home.view') ?></a>
    </footer>
</article>