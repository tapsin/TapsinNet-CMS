<?php declare(strict_types=1); ?>
<?php $list = flashes(); ?>
<?php if ($list !== []): ?>
    <div class="flash-stack" role="status" aria-live="polite">
        <?php foreach ($list as $flash): ?>
            <div class="flash flash--<?= e((string) ($flash['type'] ?? 'info')) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
