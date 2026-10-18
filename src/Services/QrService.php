<?php

declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class QrService
{
    /** Genera el QR como imagen SVG en un data URI (no necesita GD ni ficheros). */
    public static function svgDataUri(string $text): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'eccLevel' => EccLevel::M,
            'drawLightModules' => false,
            'svgPreserveAspectRatio' => 'xMidYMid',
        ]);
        return (new QRCode($options))->render($text);
    }
}
