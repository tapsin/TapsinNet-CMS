<?php declare(strict_types=1); ?>
<?php
    $title = loc($row, 'title') ?: '';
    $album = $row['album'] ?? '';
    $cover = $row['cover_image'] ?? null;
    // Yol config'deki rota adından gelir ('/galeri').
    $url = item_url('gallery', $row);
    $glyph = $glyph ?? (module('gallery', 'glyph') ?? '◆');
?>
<article class="card gallery-card">
    <div class="card__media" style="aspect-ratio: 4/3;">
        <?php if ($cover): ?>
            <a href="<?= e(upload_url($cover)) ?>" class="media__ratio media__ratio--4-3" data-lightbox-src="<?= e(upload_url($cover)) ?>" aria-label="<?= e($title) ?>">
                <img src="<?= e(upload_url($cover)) ?>" alt="" loading="lazy" decoding="async" class="media__img">
            </a>
        <?php else: ?>
            <?= partial('site.partials.card._no-image', ['glyph' => $glyph]) ?>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <h3 class="card__title">
            <?php if ($url): ?>
                <a href="<?= e($url) ?>"><?= e($title) ?></a>
            <?php else: ?>
                <span><?= e($title) ?></span>
            <?php endif; ?>
        </h3>
        <?php if ($album !== ''): ?>
            <p class="card__meta u-muted"><?= e($album) ?></p>
        <?php endif; ?>
    </div>
</article>