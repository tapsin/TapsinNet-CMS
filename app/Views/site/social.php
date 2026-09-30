<?php declare(strict_types=1); ?>
<?php /** SOSYAL MEDYA AKIŞI — ham embed HTML saklanmaz; sağlayıcıya göre link kurulur. */ ?>
<section class="section section--tight">
    <div class="wrap">
        <div class="section__head section__head--simple">
            <h1 class="section__title"><?= e(t('social.title')) ?></h1>
            <p class="section__text"><?= e(str_limit(\Models\Settings::get('site_description', t('social.intro')), 200)) ?></p>
        </div>

        <?php if ($paginator->isEmpty()): ?>
            <div class="empty-state">
                <div class="empty-state__glyph" aria-hidden="true">◈</div>
                <h2 class="empty-state__title"><?= e(t('home.empty')) ?></h2>
            </div>
        <?php else: ?>
            <div class="card-grid card-grid--3">
                <?php foreach ($rows as $row): ?>
                    <?= card_for('social', $row) ?>
                <?php endforeach; ?>
            </div>
            <?= $paginator->links('site.partials.pagination') ?>
        <?php endif; ?>
    </div>
</section>
