<?php declare(strict_types=1); ?>
<div class="lightbox" role="dialog" aria-modal="true" aria-label="<?= t('app.loading') ?>" hidden>
    <button type="button" class="lightbox__close" aria-label="<?= t('app.close') ?>" data-lightbox-close>
        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
    <button type="button" class="lightbox__prev" aria-label="Önceki" data-lightbox-prev hidden>
        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
    </button>
    <button type="button" class="lightbox__next" aria-label="Sonraki" data-lightbox-next hidden>
        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
    </button>
    <div class="lightbox__dialog">
        <img class="lightbox__img" src="" alt="" loading="lazy" decoding="async">
        <figcaption class="lightbox__caption"></figcaption>
    </div>
</div>