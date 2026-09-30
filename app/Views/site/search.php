<?php declare(strict_types=1); ?>
<?php
/** ARAMA — yalnızca AKTİF modüller taranır. Sonuç metni sunucuda <mark> ile vurgulanmıştır. */
$hasQuery = mb_strlen(trim($term)) >= 2;
?>
<section class="section section--tight">
    <div class="wrap wrap--narrow">
        <div class="section__head section__head--simple">
            <h1 class="section__title"><?= e($hasQuery ? t('search.results_for', ['term' => $term]) : t('search.title')) ?></h1>
            <?php if ($hasQuery): ?>
                <p class="section__text"><?= e(t('search.found', ['count' => number_format((int) $total, 0, ',', '.')])) ?></p>
            <?php endif; ?>
        </div>

        <form class="searchbar" method="get" action="<?= e(url('/ara')) ?>" role="search">
            <label class="visually-hidden" for="q"><?= e(t('search.placeholder')) ?></label>
            <input type="search" id="q" name="q" class="input" value="<?= e($term) ?>"
                   placeholder="<?= e(t('search.placeholder')) ?>" autocomplete="off" autofocus>
            <button type="submit" class="searchbar__btn" aria-label="<?= e(t('search.submit')) ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M11 11l4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </form>

        <?php if (!$hasQuery): ?>
            <div class="btn-row">
                <?php foreach ($navModules as $slug): ?>
                    <a href="<?= e(module_url($slug)) ?>" class="filter-chip"><?= e(\Core\ModuleRegistry::name($slug)) ?></a>
                <?php endforeach; ?>
            </div>

        <?php elseif ($groups === []): ?>
            <div class="empty-state">
                <div class="empty-state__glyph" aria-hidden="true">⌕</div>
                <h2 class="empty-state__title"><?= e(t('search.none')) ?></h2>
                <p class="empty-state__text"><?= e(t('search.none_hint')) ?></p>
            </div>

        <?php else: ?>
            <?php foreach ($groups as $group): ?>
                <section class="rail" aria-labelledby="srch-<?= e($group['module']) ?>">
                    <div class="rail__head">
                        <h2 class="rail__title" id="srch-<?= e($group['module']) ?>">
                            <?= e($group['name']) ?>
                            <span class="rail__count"><?= e(number_format(count($group['items']), 0, ',', '.')) ?></span>
                        </h2>
                        <a href="<?= e($group['url']) ?>" class="rail__more"><?= e(t('search.in_module')) ?></a>
                    </div>
                    <ul class="hairline-stack">
                        <?php foreach ($group['items'] as $item): ?>
                            <li class="search-result">
                                <?php if (!empty($item['image'])): ?>
                                    <span class="search-result__thumb">
                                        <img src="<?= e($item['image']) ?>" alt="" class="thumb" loading="lazy" decoding="async">
                                    </span>
                                <?php endif; ?>
                                <span class="search-result__body">
                                    <a href="<?= e($item['url']) ?>" class="search-result__title"><?= e($item['title']) ?></a>
                                    <?php if (!empty($item['excerpt'])): ?>
                                        <span class="search-result__text"><?= $item['excerpt'] ?></span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
