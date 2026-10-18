<?php
/** @var int $count sellos actuales  @var int $goal sellos para la bebida gratis */
?>
<ol class="stamps" aria-label="<?= e($count) ?> de <?= e($goal) ?> sellos">
    <?php for ($i = 1; $i <= $goal; $i++): ?>
        <li class="stamp<?= $i <= $count ? ' is-on' : '' ?><?= $i === $goal ? ' is-prize' : '' ?>">
            <svg width="26" height="26" aria-hidden="true"><use href="#<?= $i === $goal ? 'i-cup' : 'i-bean' ?>"/></svg>
            <span class="stamp-n"><?= $i === $goal ? 'Gratis' : $i ?></span>
        </li>
    <?php endfor; ?>
</ol>
