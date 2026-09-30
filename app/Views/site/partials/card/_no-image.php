<?php declare(strict_types=1); ?>
<?php
    $glyph = $glyph ?? (module($module ?? '', 'glyph') ?? '◆');
?>
<div class="card__glyph" aria-hidden="true"><?= e($glyph) ?></div>