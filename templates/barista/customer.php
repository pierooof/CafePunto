<?php
$count = (int) $customer['stamps'];
$missing = $goal - $count;
$action = '/barista/customers/' . (int) $customer['id'];
?>
<a class="back" href="/barista">← Panel</a>

<section class="card ticket">
    <div class="ticket-head">
        <div>
            <p class="eyebrow"><?= e($customer['phone']) ?></p>
            <h1><?= e($customer['name']) ?></h1>
        </div>
        <span class="counter"><b><?= e($count) ?></b>/<?= e($goal) ?></span>
    </div>

    <?php require __DIR__ . '/../partials/stamps.php'; ?>

    <?php if ($missing <= 0): ?>
        <form method="post" action="<?= e($action) ?>/redeem" class="actions-bar">
            <?= csrf_field() ?>
            <button class="btn btn-prize btn-block btn-lg" type="submit">
                <svg width="24" height="24" aria-hidden="true"><use href="#i-cup"/></svg> Canjear bebida gratis
            </button>
        </form>
    <?php else: ?>
        <form method="post" action="<?= e($action) ?>/stamps" class="actions-bar stamp-buttons">
            <?= csrf_field() ?>
            <?php foreach ([1, 2, 3] as $n): ?>
                <button class="btn <?= $n === 1 ? 'btn-primary' : 'btn-outline' ?> btn-lg" type="submit" name="amount" value="<?= $n ?>" <?= $n > $missing ? 'disabled' : '' ?>>+<?= $n ?></button>
            <?php endforeach; ?>
        </form>
        <p class="ticket-msg">Faltan <strong><?= e($missing) ?></strong> <?= $missing === 1 ? 'sello' : 'sellos' ?> para la bebida gratis.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../partials/history.php'; ?>
