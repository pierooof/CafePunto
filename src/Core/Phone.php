<?php

declare(strict_types=1);

namespace App\Core;

final class Phone
{
    /**
     * Normaliza un teléfono: quita espacios, puntos, guiones y paréntesis,
     * convierte el prefijo 00 en + y exige entre 9 y 15 dígitos.
     * Devuelve null si no es válido.
     */
    public static function normalize(string $raw): ?string
    {
        $clean = preg_replace('/[\s.\-()]/', '', $raw) ?? '';
        if (str_starts_with($clean, '00')) {
            $clean = '+' . substr($clean, 2);
        }
        return preg_match('/^\+?\d{9,15}$/', $clean) === 1 ? $clean : null;
    }

    /** Solo los dígitos, para búsquedas parciales ("612 34" -> "61234"). */
    public static function digits(string $raw): string
    {
        return preg_replace('/\D/', '', $raw) ?? '';
    }
}
