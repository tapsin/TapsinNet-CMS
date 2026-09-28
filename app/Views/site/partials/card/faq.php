<?php declare(strict_types=1); ?>
<?php
    $question = loc($row, 'question') ?: '';
    $answer = loc($row, 'answer') ?: '';
    $category = $row['category'] ?? '';
    $glyph = $glyph ?? (module('faq', 'glyph') ?? '◆');
?>
<article class="card faq-card">
    <div class="card__body">
        <?php if ($category !== ''): ?>
            <span class="tag tag--outline"><?= e($category) ?></span>
        <?php endif; ?>
        <details class="faq__item">
            <summary class="faq__question" aria-expanded="false">
                <?= e($question) ?>
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </summary>
            <div class="faq__answer">
                <?= safe_html($answer) ?>
            </div>
        </details>
    </div>
</article>