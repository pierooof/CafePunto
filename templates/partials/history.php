<?php /** @var list<array<string, mixed>> $history */ ?>
<section class="card">
    <h2>Historial</h2>
    <?php if ($history === []): ?>
        <p class="muted">Todavía no hay movimientos.</p>
    <?php else: ?>
        <ul class="log">
            <?php foreach ($history as $row): ?>
                <li class="log-row log-<?= e($row['type']) ?>">
                    <span class="log-dot" aria-hidden="true"></span>
                    <div>
                        <strong>
                            <?= $row['type'] === 'redeem'
                                ? 'Bebida gratis canjeada'
                                : '+' . e($row['amount']) . ($row['amount'] == 1 ? ' sello' : ' sellos') ?>
                        </strong>
                        <small><?= e(date_es($row['created_at'])) ?><?= $row['staff_name'] ? ' · ' . e($row['staff_name']) : '' ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
