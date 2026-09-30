<?php declare(strict_types=1); ?>
<?php
    $title = $metaTitle ?? '';
    $desc  = $metaDesc ?? setting('meta_description', '');
    $image = $ogImage ?? null;
?>
<?php if ($title !== ''): ?>
    <title><?= e($title) ?></title>
<?php endif; ?>
<?php if ($desc !== ''): ?>
    <meta name="description" content="<?= e($desc) ?>">
    <meta property="og:description" content="<?= e($desc) ?>">
    <meta name="twitter:description" content="<?= e($desc) ?>">
<?php endif; ?>
<?php if ($image !== null): ?>
    <meta property="og:image" content="<?= e($image) ?>">
    <meta name="twitter:image" content="<?= e($image) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= e($title ?: $siteName) ?>">
<meta property="og:url" content="<?= e(url($currentPath)) ?>">
<meta name="twitter:card" content="summary_large_image">