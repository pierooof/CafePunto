<?php
/**
 * Campo de contraseña con botón mostrar/ocultar (el botón lo activa assets/app.js).
 *
 * @var string  $id           id del input
 * @var string  $autocomplete current-password | new-password
 * @var ?string $fieldError   mensaje de error del campo (opcional)
 * @var ?string $hint         ayuda bajo el campo (opcional; si existe, exige 8+ caracteres)
 */
?>
<div class="field <?= !empty($fieldError) ? 'has-error' : '' ?>">
    <label for="<?= e($id) ?>">Contraseña</label>
    <div class="pw">
        <input type="password" id="<?= e($id) ?>" name="password" autocomplete="<?= e($autocomplete) ?>"
               autocapitalize="none" spellcheck="false" <?= !empty($hint) ? 'minlength="8"' : '' ?> required>
        <button type="button" class="pw-toggle" data-pw-toggle="<?= e($id) ?>" aria-pressed="false" aria-label="Mostrar contraseña" hidden>
            <svg class="i-show" width="22" height="22" aria-hidden="true"><use href="#i-eye"/></svg>
            <svg class="i-hide" width="22" height="22" aria-hidden="true"><use href="#i-eye-off"/></svg>
        </button>
    </div>
    <?php if (!empty($fieldError)): ?><small><?= e($fieldError) ?></small>
    <?php elseif (!empty($hint)): ?><small class="hint"><?= e($hint) ?></small><?php endif; ?>
</div>
