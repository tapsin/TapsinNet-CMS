<?php declare(strict_types=1); ?>
<?php
    $title = loc($row, 'title') ?: '';
    $album = $row['album'] ?? '';
    $duration = $row['duration'] ?? '';
    $cover = $row['cover_image'] ?? null;
    // Yol config'deki rota adından gelir ('/videolar'). Elle yazılan
    // '/video/' yolu rotada yoktu.
    $url = item_url('videos', $row);
    $glyph = $glyph ?? (module('videos', 'glyph') ?? '◆');
    $embedUrl = video_embed_url($row);
?>
<article class="card video-card">
    <div class="card__media" style="aspect-ratio: 16/9; position: relative;">
        <?php if ($cover): ?>
            <img src="<?= e(upload_url($cover)) ?>" alt=""
                 loading="lazy" decoding="async"
                 class="media__img"
                 style="width: 100%; height: 100%; object-fit: cover;">
        <?php else: ?>
            <?= partial('site.partials.card._no-image', ['glyph' => $glyph]) ?>
        <?php endif; ?>
        <?php if ($embedUrl): ?>
            <button type="button"
                    class="card__play"
                    data-video-id="<?= e($row['video_id'] ?? '') ?>"
                    data-video-embed="<?= e($embedUrl) ?>"
                    aria-label="<?= t('detail.watch') ?>: <?= e($title) ?>">
                <svg aria-hidden="true" width="48" height="48" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </button>
        <?php endif; ?>
        <?php if ($duration !== ''): ?>
            <span class="card__duration" aria-label="<?= t('detail.duration') ?>"><?= e($duration) ?></span>
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
    <footer class="card__foot">
        <?php if ($embedUrl): ?>
            <button type="button"
                    class="btn btn--primary btn--block"
                    data-video-id="<?= e($row['video_id'] ?? '') ?>"
                    data-video-embed="<?= e($embedUrl) ?>"
                    aria-label="<?= t('detail.watch') ?>: <?= e($title) ?>">
                <?= t('detail.watch') ?>
            </button>
        <?php else: ?>
            <?php if ($url): ?>
                <a href="<?= e($url) ?>" class="btn btn--quiet btn--block"><?= t('home.view') ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </footer>
</article>