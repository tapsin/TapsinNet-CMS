<?php declare(strict_types=1); ?>
<?php
    $caption = loc($row, 'caption') ?: '';
    $provider = $row['provider'] ?? '';
    $postUrl = $row['post_url'] ?? '';
    $cover = $row['cover_image'] ?? null;
    $published = $row['published_at'] ?? $row['created_at'] ?? '';
    $glyph = $glyph ?? (module('social', 'glyph') ?? '◆');
    $url = $postUrl ?: '#';
?>
<article class="card social-card">
    <?php if ($cover): ?>
        <div class="card__media" style="aspect-ratio: 4/3;">
            <img src="<?= e(upload_url($cover)) ?>" alt=""
                 loading="lazy" decoding="async"
                 class="media__img"
                 style="width: 100%; height: 100%; object-fit: cover;">
        </div>
    <?php else: ?>
        <div class="card__media" style="aspect-ratio: 4/3;">
            <?= partial('site.partials.card._no-image', ['glyph' => $glyph]) ?>
        </div>
    <?php endif; ?>
    <div class="card__body">
        <div class="card__meta">
            <?php if ($provider !== ''): ?>
                <span class="tag tag--accent"><?= e(ucfirst($provider)) ?></span>
            <?php endif; ?>
            <?php if ($published !== ''): ?>
                <time datetime="<?= e($published) ?>"><?= e(format_date($published)) ?></time>
            <?php endif; ?>
        </div>
        <?php if ($caption !== ''): ?>
            <p class="card__text"><?= e(str_limit($caption, 160)) ?></p>
        <?php endif; ?>
    </div>
    <footer class="card__foot">
        <?php if ($postUrl): ?>
            <a href="<?= e($postUrl) ?>" class="btn btn--quiet" target="_blank" rel="noopener noreferrer">
                <?= t('social.view_post') ?>
            </a>
        <?php else: ?>
            <span class="btn btn--quiet btn--disabled" aria-disabled="true"><?= t('social.view_post') ?></span>
        <?php endif; ?>
    </footer>
</article>