<?php declare(strict_types=1); ?>
<?php
    $name = $row['name'] ?? '';
    $role = loc($row, 'role') ?: '';
    $company = loc($row, 'company') ?: '';
    $bio = loc($row, 'bio') ?: '';
    $avatar = $row['avatar'] ?? null;
    // Yol config'deki rota adından gelir ('/profiller'). Elle yazılan
    // '/profil/' yolu rotada yoktu ve her profil kartı 404'e düşüyordu.
    $url = item_url('profiles', $row);
    $glyph = $glyph ?? (module('profiles', 'glyph') ?? '◆');
?>
<article class="card profile-card">
    <div class="card__media">
        <?php if ($avatar): ?>
            <img src="<?= e(upload_url($avatar)) ?>" alt="<?= e($name) ?>"
                 loading="lazy" decoding="async"
                 class="avatar avatar--lg">
        <?php else: ?>
            <div class="avatar avatar--lg" aria-hidden="true"><?= e($glyph) ?></div>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <h3 class="card__title">
            <?php if ($url): ?>
                <a href="<?= e($url) ?>"><?= e($name) ?></a>
            <?php else: ?>
                <span><?= e($name) ?></span>
            <?php endif; ?>
        </h3>
        <?php if ($role !== ''): ?>
            <p class="card__meta u-muted"><?= e($role) ?></p>
        <?php endif; ?>
        <?php if ($company !== ''): ?>
            <p class="card__meta u-muted"><?= e($company) ?></p>
        <?php endif; ?>
        <?php if ($bio !== ''): ?>
            <p class="card__text"><?= e(str_limit($bio, 120)) ?></p>
        <?php endif; ?>
    </div>
    <footer class="card__foot">
        <?php if ($url): ?>
            <a href="<?= e($url) ?>" class="btn btn--quiet"><?= t('home.view') ?></a>
        <?php endif; ?>
    </footer>
</article>