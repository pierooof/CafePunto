<section class="card">
    <a class="back" href="/barista">← Panel</a>
    <h1>Resultados</h1>
    <form method="get" action="/barista/search" class="search" role="search">
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="Teléfono o nombre" aria-label="Teléfono o nombre del cliente" required minlength="2">
        <button class="btn btn-primary" type="submit"><svg width="20" height="20" aria-hidden="true"><use href="#i-search"/></svg><span>Buscar</span></button>
    </form>

    <?php if ($results === []): ?>
        <p class="muted empty">
            <?= mb_strlen($query) < 2 ? 'Escribe al menos 2 caracteres.' : 'No hay ningún cliente que coincida con «' . e($query) . '».' ?>
        </p>
    <?php else: ?>
        <ul class="rank results">
            <?php foreach ($results as $row): ?>
                <li>
                    <a href="/barista/customers/<?= e($row['id']) ?>">
                        <span class="rank-name"><?= e($row['name']) ?></span>
                        <span class="rank-meta"><?= e($row['phone']) ?> · <?= e($row['stamps']) ?>/10</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
