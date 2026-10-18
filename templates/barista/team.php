<a class="back" href="/barista">← Panel</a>

<section class="card">
    <h1>Equipo</h1>
    <p class="muted">Baristas con acceso al panel.</p>

    <ul class="team">
        <?php foreach ($team as $member): ?>
            <li>
                <div class="team-info">
                    <strong><?= e($member['name']) ?></strong>
                    <span class="team-meta"><?= e($member['phone']) ?></span>
                </div>
                <div class="team-tags">
                    <?php if ((int) $member['is_admin'] === 1): ?><span class="badge">Admin</span><?php endif; ?>
                    <?php if ((int) $member['id'] === $me): ?><span class="badge badge-soft">Tú</span><?php endif; ?>
                </div>
                <?php if ((int) $member['id'] !== $me): ?>
                    <form method="post" action="/barista/team/<?= (int) $member['id'] ?>/delete" data-confirm="¿Eliminar a <?= e($member['name']) ?> del equipo? Perderá el acceso al panel.">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="card">
    <h2>Añadir barista</h2>
    <form method="post" action="/barista/team" class="form" novalidate>
        <?= csrf_field() ?>
        <label class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <span>Nombre</span>
            <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" maxlength="60" required>
            <?php if (isset($errors['name'])): ?><small><?= e($errors['name']) ?></small><?php endif; ?>
        </label>
        <label class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
            <span>Teléfono</span>
            <input type="tel" name="phone" value="<?= e($old['phone'] ?? '') ?>" inputmode="tel" placeholder="600 123 456" required>
            <?php if (isset($errors['phone'])): ?><small><?= e($errors['phone']) ?></small><?php endif; ?>
        </label>
        <?php
        $id = 'team-password';
        $autocomplete = 'new-password';
        $fieldError = $errors['password'] ?? null;
        $hint = 'Mínimo 8 caracteres. Compártela con la persona por un canal seguro.';
        require __DIR__ . '/../partials/password_field.php';
        ?>
        <label class="check">
            <input type="checkbox" name="is_admin" value="1" <?= !empty($old['is_admin']) ? 'checked' : '' ?>>
            <span>Puede gestionar el equipo</span>
        </label>
        <button class="btn btn-primary btn-block" type="submit">Añadir al equipo</button>
    </form>
</section>
