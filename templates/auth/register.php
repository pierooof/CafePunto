<section class="card auth">
    <h1>Crea tu tarjeta</h1>
    <p class="muted">Tarda menos de un minuto.</p>

    <form method="post" action="/register" class="form" novalidate>
        <?= csrf_field() ?>
        <label class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <span>Nombre</span>
            <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" maxlength="60" required autofocus>
            <?php if (isset($errors['name'])): ?><small><?= e($errors['name']) ?></small><?php endif; ?>
        </label>
        <label class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
            <span>Teléfono</span>
            <input type="tel" name="phone" value="<?= e($old['phone'] ?? '') ?>" inputmode="tel" autocomplete="tel" placeholder="600 123 456" required>
            <?php if (isset($errors['phone'])): ?><small><?= e($errors['phone']) ?></small><?php endif; ?>
        </label>
        <label class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
            <span>Contraseña</span>
            <input type="password" name="password" autocomplete="new-password" minlength="8" required>
            <?php if (isset($errors['password'])): ?><small><?= e($errors['password']) ?></small><?php endif; ?>
        </label>
        <button class="btn btn-primary btn-block" type="submit">Crear mi tarjeta</button>
    </form>

    <p class="muted center">¿Ya tienes cuenta? <a href="/login">Entra</a></p>
</section>
