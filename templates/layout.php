<?php
/** @var string $content */
use App\Core\Auth;
use App\Core\Session;

$user = Auth::user();
$flash = Session::pullFlash();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3b2314">
    <title><?= e($title ?? 'CaféPunto') ?> · CaféPunto</title>
    <meta name="description" content="Tarjeta de fidelización digital para cafeterías: suma sellos y canjea tu bebida gratis.">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="CaféPunto">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Nunito:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
</head>
<body>
<svg class="sprite" aria-hidden="true" focusable="false">
    <symbol id="i-bean" viewBox="0 0 24 24">
        <ellipse cx="12" cy="12" rx="6.5" ry="10" transform="rotate(28 12 12)" fill="currentColor"/>
        <path class="bean-line" d="M9 4.5c4.5 3 2.5 6.5 3.5 8.5s2 4 2.5 6" fill="none" stroke-width="1.6" stroke-linecap="round"/>
    </symbol>
    <symbol id="i-cup" viewBox="0 0 24 24">
        <path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9Zm13 1h1.5a2.5 2.5 0 0 1 0 5H17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8 3.5c-1 1 1 1.8 0 3m4-3c-1 1 1 1.8 0 3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </symbol>
    <symbol id="i-scan" viewBox="0 0 24 24">
        <path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2M7 12h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </symbol>
    <symbol id="i-eye" viewBox="0 0 24 24">
        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
    </symbol>
    <symbol id="i-eye-off" viewBox="0 0 24 24">
        <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.7A16.6 16.6 0 0 0 2 12s3.6 7 10 7c1.7 0 3.2-.4 4.5-1M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </symbol>
    <symbol id="i-search" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </symbol>
</svg>

<header class="topbar">
    <a class="brand" href="/">
        <svg class="icon" width="26" height="26"><use href="#i-cup"/></svg>
        <span>Café<b>Punto</b></span>
    </a>
    <?php if ($user): ?>
        <form method="post" action="/logout" class="topbar-user">
            <?= csrf_field() ?>
            <span class="who"><?= e(explode(' ', (string) $user['name'])[0]) ?></span>
            <button class="btn btn-ghost btn-sm" type="submit">Salir</button>
        </form>
    <?php endif; ?>
</header>

<main class="wrap">
    <?php foreach ($flash as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>

<footer class="foot">Hecho con ☕ por <a href="https://pierooof.dev" rel="noopener">pierooof.dev</a></footer>

<script src="<?= e(asset('app.js')) ?>" defer></script>
<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
