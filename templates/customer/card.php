<?php
$count = (int) $customer['stamps'];
$missing = $goal - $count;
?>
<section class="card ticket">
    <div class="ticket-head">
        <div>
            <p class="eyebrow">Tarjeta de</p>
            <h1><?= e($customer['name']) ?></h1>
        </div>
        <span class="counter"><b><?= e($count) ?></b>/<?= e($goal) ?></span>
    </div>

    <?php require __DIR__ . '/../partials/stamps.php'; ?>

    <p class="ticket-msg">
        <?php if ($missing <= 0): ?>
            <strong>¡Tarjeta completa!</strong> Enseña tu QR al barista para canjear tu bebida gratis.
        <?php else: ?>
            Te <?= $missing === 1 ? 'falta' : 'faltan' ?> <strong><?= e($missing) ?></strong> <?= $missing === 1 ? 'sello' : 'sellos' ?> para tu bebida gratis.
        <?php endif; ?>
    </p>
</section>

<section class="card qr-card">
    <h2>Tu código QR</h2>
    <p class="muted">Enséñalo en la barra para que el barista te sume sellos.</p>
    <img class="qr" src="<?= e($qr) ?>" width="220" height="220" alt="Código QR personal de <?= e($customer['name']) ?>">
    <p class="phone"><?= e($customer['phone']) ?></p>
</section>

<?php require __DIR__ . '/../partials/history.php'; ?>
