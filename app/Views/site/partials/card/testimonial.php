<?php declare(strict_types=1); ?>
<?php
    $quote = loc($row, 'quote') ?: '';
    $author = $row['author_name'] ?? '';
    $role = loc($row, 'author_title') ?: '';
    $company = loc($row, 'author_company') ?: '';
    $rating = $row['rating'] ?? null;
    $avatar = $row['author_avatar'] ?? null;
    $glyph = $glyph ?? (module('testimonials', 'glyph') ?? '◆');
?>
<article class="card testimonial-card">
    <div class="card__body">
        <?php if ($rating): ?>
            <div class="rating" aria-label="<?= t('detail.score', [], 'en') ?>: <?= e(number_format((float) $rating, 1, ',', '.')) ?> / 5">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24"
                         class="<?= $i <= (float) $rating ? 'rating__star--filled' : '' ?>">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        <blockquote class="card__quote">
            <p><?= e($quote) ?></p>
        </blockquote>
        <footer class="card__author">
            <?php if ($avatar): ?>
                <img src="<?= e(upload_url($avatar)) ?>" alt=""
                     loading="lazy" decoding="async"
                     class="avatar">
            <?php endif; ?>
            <div>
                <cite class="card__author-name"><?= e($author) ?></cite>
                <?php if ($role !== '' || $company !== ''): ?>
                    <p class="card__author-meta u-muted">
                        <?php if ($role !== ''): ?><?= e($role) ?><?php endif; ?>
                        <?php if ($role !== '' && $company !== ''): ?>, <?php endif; ?>
                        <?php if ($company !== ''): ?><?= e($company) ?><?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </footer>
    </div>
</article>
