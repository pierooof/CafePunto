<section class="finder card">
    <h1>Buscar cliente</h1>
    <form method="get" action="/barista/search" class="search" role="search">
        <input type="search" name="q" placeholder="Teléfono o nombre" inputmode="search" autocomplete="off" aria-label="Teléfono o nombre del cliente" required minlength="2" autofocus>
        <button class="btn btn-primary" type="submit"><svg width="20" height="20" aria-hidden="true"><use href="#i-search"/></svg><span>Buscar</span></button>
    </form>
    <button class="btn btn-outline btn-block" type="button" id="scan-open" hidden>
        <svg width="20" height="20" aria-hidden="true"><use href="#i-scan"/></svg> Escanear QR
    </button>
</section>

<div class="scanner" id="scanner" hidden>
    <video id="scan-video" playsinline muted></video>
    <div class="scan-frame" aria-hidden="true"></div>
    <p class="scan-hint" id="scan-hint">Apunta al QR del cliente</p>
    <button class="btn btn-light" type="button" id="scan-close">Cerrar</button>
</div>

<section class="stats" aria-label="Estadísticas">
    <article class="stat">
        <span class="stat-n"><?= e($stampsToday) ?></span>
        <span class="stat-l">Sellos hoy</span>
    </article>
    <article class="stat">
        <span class="stat-n"><?= e($redeemsMonth) ?></span>
        <span class="stat-l">Canjes este mes</span>
    </article>
    <article class="stat">
        <span class="stat-n"><?= e($customers) ?></span>
        <span class="stat-l">Clientes</span>
    </article>
</section>

<section class="card">
    <h2>Clientes más fieles</h2>
    <?php if ($top === []): ?>
        <p class="muted">Aún no hay actividad.</p>
    <?php else: ?>
        <ol class="rank">
            <?php foreach ($top as $i => $row): ?>
                <li>
                    <a href="/barista/customers/<?= e($row['id']) ?>">
                        <span class="rank-pos"><?= $i + 1 ?></span>
                        <span class="rank-name"><?= e($row['name']) ?></span>
                        <span class="rank-meta"><?= e($row['total_stamps']) ?> sellos · <?= e($row['redeems']) ?> <?= (int) $row['redeems'] === 1 ? 'canje' : 'canjes' ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
