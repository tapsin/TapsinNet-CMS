<?php declare(strict_types=1); ?>
<?php $flashes = flashes(); ?>
<?php if (!empty($flashes)): ?>
    <div class="flash-stack" role="region" aria-live="polite" aria-label="<?= t('app.loading') ?>">
        <?php foreach ($flashes as $flash): ?>
            <?php
                $type = $flash['type'] ?? 'info';
                $message = $flash['message'] ?? '';
            ?>
            <div class="flash flash--<?= e($type) ?>" role="alert">
                <p><?= e($message) ?></p>
                <button type="button" class="flash__close" aria-label="<?= t('app.close') ?>" data-flash-close>
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>