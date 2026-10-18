<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ManifestTest extends TestCase
{
    public function testManifestIsInstallableAndIconsExist(): void
    {
        $public = dirname(__DIR__) . '/public';
        $manifest = json_decode((string) file_get_contents($public . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach (['name', 'short_name', 'start_url', 'display', 'theme_color', 'background_color'] as $key) {
            $this->assertNotEmpty($manifest[$key], "Falta $key en el manifest");
        }
        $this->assertSame('standalone', $manifest['display']);

        $sizes = [];
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists($public . $icon['src']);
            $sizes[] = $icon['sizes'] . ' ' . $icon['purpose'];
        }
        // Chrome exige iconos de 192 y 512; el maskable evita que Android recorte el logo.
        $this->assertContains('192x192 any', $sizes);
        $this->assertContains('512x512 any', $sizes);
        $this->assertContains('512x512 maskable', $sizes);
    }

    public function testAppleTouchIconIsOpaqueAndAtTheSiteRoot(): void
    {
        // La URL del <link> y la de la raíz (la que iOS prueba por defecto) deben existir.
        $this->assertFileExists(dirname(__DIR__) . '/public/assets/icons/touch-icon-180-v2.png');
        $path = dirname(__DIR__) . '/public/apple-touch-icon.png';
        $this->assertFileExists($path);
        [$w, $h, $type] = getimagesize($path);
        $this->assertSame([180, 180, IMAGETYPE_PNG], [$w, $h, $type]);
        // Byte 25 del PNG = tipo de color; 2 = RGB sin canal alfa.
        $this->assertSame(2, ord((string) file_get_contents($path, false, null, 25, 1)));
    }
}
