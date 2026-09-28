<?php declare(strict_types=1); ?>
<?php
    $title = loc($row, 'title') ?: '';
    $issuer = loc($row, 'issuer') ?: '';
    $issueDate = $row['issue_date'] ?? '';
    $credential = $row['credential_id'] ?? '';
    $score = $row['score'] ?? null;
    $cover = $row['cover_image'] ?? null;
    $pdf = $row['pdf_path'] ?? null;
    $verifyUrl = $row['verify_url'] ?? null;
    $url = $row['slug'] ? url('/sertifika/' . $row['slug']) : '#';
    $glyph = $glyph ?? (module('certificates', 'glyph') ?? '◆');
?>
<article class="card certificate-card">
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
        <dl class="dl card__meta">
            <?php if ($issuer !== ''): ?>
                <div class="dl__row">
                    <dt class="dl__key"><?= t('detail.issuer') ?></dt>
                    <dd class="dl__value"><?= e($issuer) ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($issueDate !== ''): ?>
                <div class="dl__row">
                    <dt class="dl__key"><?= t('detail.date') ?></dt>
                    <dd class="dl__value"><?= e(format_date($issueDate)) ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($credential !== ''): ?>
                <div class="dl__row">
                    <dt class="dl__key"><?= t('detail.credential') ?></dt>
                    <dd class="dl__value u-mono"><?= e($credential) ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($score !== null): ?>
                <div class="dl__row">
                    <dt class="dl__key"><?= t('detail.score') ?></dt>
                    <dd class="dl__value"><?= e(number_format((float) $score, 1, ',', '.')) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>
    <footer class="card__foot btn-row">
        <a href="<?= e($url) ?>" class="btn btn--quiet"><?= t('home.view') ?></a>
        <?php if ($pdf): ?>
            <a href="<?= e(upload_url($pdf)) ?>" class="btn btn--ghost" download><?= t('detail.download') ?></a>
        <?php endif; ?>
        <?php if ($verifyUrl): ?>
            <a href="<?= e($verifyUrl) ?>" class="btn btn--ghost" target="_blank" rel="noopener noreferrer"><?= t('detail.verify') ?></a>
        <?php endif; ?>
    </footer>
</article>