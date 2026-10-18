<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Error de reglas de negocio; el mensaje (en español) se puede mostrar al usuario. */
final class LoyaltyException extends RuntimeException
{
}
