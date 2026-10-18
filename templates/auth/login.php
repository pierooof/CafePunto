<section class="card auth">
    <h1>Entrar</h1>
    <p class="muted">Usa tu teléfono y tu contraseña.</p>

    <?php if ($error): ?>
        <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login" class="form" novalidate>
        <?= csrf_field() ?>
        <label class="field">
            <span>Teléfono</span>
            <input type="tel" name="phone" value="<?= e($phone) ?>" inputmode="tel" autocomplete="tel" placeholder="600 123 456" required autofocus>
        </label>
        <label class="field">
            <span>Contraseña</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    </form>

    <p class="muted center">¿Primera vez? <a href="/register">Crea tu tarjeta</a></p>
</section>

<?php if ($demoHint): ?>
    <section class="card demo">
        <p class="eyebrow">Modo demo</p>
        <dl>
            <dt>Barista</dt><dd><code>600000001</code> · <code>barista123</code></dd>
            <dt>Cliente</dt><dd><code>600000002</code> · <code>cliente123</code></dd>
        </dl>
    </section>
<?php endif; ?>
