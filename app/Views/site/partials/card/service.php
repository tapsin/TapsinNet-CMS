<?php declare(strict_types=1); ?>
<?php
    $title = loc($row, 'title') ?: '';
    $summary = loc($row, 'summary') ?: '';
    $cover = $row['cover_image'] ?? null;
    $url = $row['slug'] ? url('/hizmetler/' . $row['slug']) : '#';
    $glyph = $glyph ?? (module('services', 'glyph') ?? '◆');
?>
<article class="card service-card">
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
        <h3 class="card__title">
            <a href="<?= e($url) ?>"><?= e($title) ?></a>
        </h3>
        <?php if ($summary !== ''): ?>
            <p class="card__text"><?= e(str_limit($summary, 120)) ?></p>
        <?php endif; ?>
    </div>
    <footer class="card__foot">
        <a href="<?= e($url) ?>" class="btn btn--quiet"><?= t('home.view') ?></a>
    </footer>
</article>