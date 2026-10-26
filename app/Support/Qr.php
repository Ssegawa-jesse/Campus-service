<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Draws QR codes as inline SVG (no GD or Imagick extension needed).
 */
class Qr
{
    public static function svg(string $text, int $size = 120, int $margin = 0): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd));

        // Drop the XML declaration so the SVG can be placed directly in HTML.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $writer->writeString($text));
    }
}
