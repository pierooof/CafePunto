<?php

declare(strict_types=1);

use App\Core\Csrf;

/** Escapa para HTML. Úsalo en TODA salida de datos en las plantillas. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Campo oculto con el token CSRF para formularios POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Ruta de un fichero de public/assets con la fecha de modificación para romper la caché. */
function asset(string $file): string
{
    $mtime = @filemtime(dirname(__DIR__) . '/public/assets/' . $file) ?: 0;
    return '/assets/' . $file . '?v=' . $mtime;
}

/** "2026-10-18 17:05:00" -> "18 oct 2026, 17:05" */
function date_es(string $datetime): string
{
    static $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($datetime);
    return date('j', $t) . ' ' . $months[(int) date('n', $t) - 1] . ' ' . date('Y, H:i', $t);
}
