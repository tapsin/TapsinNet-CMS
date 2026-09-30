<?php declare(strict_types=1); ?>
<section class="panel admin-search-page">
    <div class="panel__head">
        <h2 class="panel__title">Yönetim araması</h2>
    </div>
    <div class="panel__body">
        <form class="admin-search-page__form" method="get" action="<?= e(url('/admin/ara')) ?>" role="search">
            <label class="visually-hidden" for="admin-page-search">Kayıtlarda ara</label>
            <input type="search" id="admin-page-search" name="q" class="input" value="<?= e($term) ?>" placeholder="Kayıtlarda ara…" autofocus>
            <button type="submit" class="admin-search-submit admin-search-page__submit" aria-label="Ara" title="Ara">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"></circle><path d="m16 16 4.5 4.5"></path></svg>
            </button>
        </form>

        <?php if (mb_strlen($term) < 2): ?>
            <div class="empty-state"><div class="empty-state__glyph" aria-hidden="true">⌕</div><h3 class="empty-state__title">Arama yapmak için en az 2 karakter yazın.</h3></div>
        <?php elseif ($results === []): ?>
            <div class="empty-state"><div class="empty-state__glyph" aria-hidden="true">⌕</div><h3 class="empty-state__title">Sonuç bulunamadı.</h3><p class="empty-state__text">“<?= e($term) ?>” için eşleşen kayıt yok.</p></div>
        <?php else: ?>
            <div class="admin-search-results">
                <?php foreach ($results as $result): ?>
                    <a class="admin-search-result" href="<?= e($result['url']) ?>">
                        <span class="admin-search-result__type"><?= e($result['type']) ?></span>
                        <strong><?= e($result['title']) ?></strong>
                        <span aria-hidden="true">→</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
