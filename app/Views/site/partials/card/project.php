<?php declare(strict_types=1); ?>
<?php
    $title = loc($row, 'title') ?: ($row['name'] ?? '');
    $client = loc($row, 'client') ?: '';
    $category = $row['category'] ?? '';
    $completed = $row['completed_at'] ?? $row['created_at'] ?? '';
    $cover = $row['cover_image'] ?? null;
    $url = $row['slug'] ? url('/isler/' . $row['slug']) : '#';
    $glyph = $glyph ?? (module('projects', 'glyph') ?? '◆');
?>
<article class="card project-card">
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
        <div class="card__meta">
            <?php if ($client !== ''): ?>
                <span><?= e($client) ?></span>
            <?php endif; ?>
            <?php if ($category !== ''): ?>
                <span><?= e($category) ?></span>
            <?php endif; ?>
            <?php if ($completed !== ''): ?>
                <time datetime="<?= e($completed) ?>"><?= e(format_date($completed)) ?></time>
            <?php endif; ?>
        </div>
    </div>
    <footer class="card__foot">
        <a href="<?= e($url) ?>" class="btn btn--quiet"><?= t('home.view') ?></a>
    </footer>
</article>