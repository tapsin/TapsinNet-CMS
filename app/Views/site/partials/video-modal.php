<?php declare(strict_types=1); ?>
<div class="video-modal" role="dialog" aria-modal="true" aria-label="<?= t('detail.watch') ?>" hidden>
    <button type="button" class="video-modal__close" aria-label="<?= t('app.close') ?>" data-video-close>
        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
    <div class="video-modal__dialog">
        <div class="video-modal__embed" data-video-embed></div>
    </div>
</div>