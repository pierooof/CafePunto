<?php

declare(strict_types=1);

namespace App\Core;

/** Renderiza plantillas PHP de templates/ dentro de templates/layout.php. */
final class View
{
    /** @param array<string, mixed> $vars */
    public static function page(string $template, array $vars = [], int $status = 200): Response
    {
        $content = self::capture($template, $vars);
        $html = self::capture('layout', $vars + ['content' => $content]);
        return new Response($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /** @param array<string, mixed> $vars */
    public static function capture(string $template, array $vars): string
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . '/templates/' . $template . '.php';
        return (string) ob_get_clean();
    }

    public static function error(int $status, string $title, string $message): Response
    {
        return self::page('error', ['title' => $title, 'message' => $message, 'code' => $status], $status);
    }
}
