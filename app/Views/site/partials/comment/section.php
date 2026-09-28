<?php declare(strict_types=1); ?>
<?php
$canComment   = $canComment ?? true;
// $module çoğul slug'dır (projects) ama Comment::TYPES tekil tip ister
// (project). Controller $commentType'ı ayrıca geçirir.
$module       = $module ?? 'project';
$commentType  = $commentType ?? $module;
$entityId     = (int) ($entityId ?? 0);
$comments     = $comments ?? [];
$commentCount = (int) ($commentCount ?? 0);
$returnUrl    = $returnUrl ?? '/';
?>
<section class="comments" aria-labelledby="comments-heading">
    <h2 id="comments-heading" class="section__title">
        <?= t('comment.title') ?>
        <?php if ($commentCount > 0): ?>
            <span class="section__count"><?= t('comment.count', ['count' => number_format($commentCount, 0, ',', '.')]) ?></span>
        <?php endif; ?>
    </h2>

    <?php if (empty($comments)): ?>
        <p class="empty-state__text"><?= t('comment.empty') ?></p>
    <?php else: ?>
        <ol class="comment-list">
            <?php foreach ($comments as $comment): ?>
                <li class="comment" id="comment-<?= e($comment['id']) ?>">
                    <article class="comment__body">
                        <header class="comment__header">
                            <strong class="comment__author"><?= e($comment['name'] ?: t('comment.anonymous')) ?></strong>
                            <time class="comment__time" datetime="<?= e($comment['created_at']) ?>">
                                <?= e(format_date($comment['created_at'])) ?>
                            </time>
                        </header>
                        <div class="comment__content">
                            <?= e($comment['body']) ?>
                        </div>
                    </article>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <?php if ($canComment): ?>
        <?= partial('site.partials.comment.form', [
            'module' => $commentType,
            'itemId' => $row['id'] ?? $entityId,
        ]) ?>
    <?php endif; ?>
</section>