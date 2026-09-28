<?php

namespace App\Services;

/**
 * Draws stylised landscape images with PHP GD so every park has a working
 * 360° tour and cover photo out of the box. Replace them with real photos
 * from the admin panel (Admin → Parks → 360° Scenes / Gallery).
 *
 * Panoramas are equirectangular (2:1) and wrap seamlessly left↔right,
 * which is what Pannellum needs.
 */
class PlaceholderImageGenerator
{
    /** Landscape themes keyed by name. Colours are [r,g,b]. */
    public const THEMES = [
        'forest' => ['sky' => [[120, 170, 200], [205, 225, 215]], 'far' => [52, 92, 70], 'near' => [24, 66, 38], 'ground' => [[34, 80, 40], [18, 45, 22]], 'feature' => 'trees'],
        'savannah' => ['sky' => [[70, 140, 210], [250, 215, 160]], 'far' => [120, 120, 90], 'near' => [150, 130, 70], 'ground' => [[196, 164, 88], [120, 96, 48]], 'feature' => 'acacia'],
        'mountain' => ['sky' => [[60, 110, 180], [200, 220, 240]], 'far' => [110, 120, 140], 'near' => [60, 80, 70], 'ground' => [[70, 100, 60], [35, 55, 35]], 'feature' => 'peaks'],
        'water' => ['sky' => [[80, 150, 215], [230, 230, 210]], 'far' => [70, 110, 80], 'near' => [45, 90, 55], 'ground' => [[60, 120, 160], [25, 70, 110]], 'feature' => 'water'],
        'sunset' => ['sky' => [[60, 50, 110], [250, 150, 70]], 'far' => [90, 60, 70], 'near' => [50, 35, 40], 'ground' => [[130, 90, 50], [60, 40, 25]], 'feature' => 'acacia'],
        'volcano' => ['sky' => [[90, 130, 190], [215, 225, 230]], 'far' => [80, 90, 100], 'near' => [40, 70, 45], 'ground' => [[50, 95, 45], [25, 50, 25]], 'feature' => 'volcano'],
    ];

    public function panorama(string $path, string $theme, int $seed = 1, int $width = 2048): void
    {
        $this->render($path, $theme, $seed, $width, intdiv($width, 2), true);
    }

    public function photo(string $path, string $theme, int $seed = 1, int $width = 1200, int $height = 700): void
    {
        $this->render($path, $theme, $seed, $width, $height, false);
    }

    private function render(string $path, string $theme, int $seed, int $w, int $h, bool $pano): void
    {
        $t = self::THEMES[$theme] ?? self::THEMES['savannah'];
        mt_srand($seed);

        $img = imagecreatetruecolor($w, $h);
        $horizon = (int) ($h * ($pano ? 0.5 : 0.58));

        // Sky gradient
        $this->gradient($img, 0, $horizon, $w, $t['sky'][0], $t['sky'][1]);

        // Sun
        $sunX = mt_rand((int) ($w * 0.1), (int) ($w * 0.9));
        $sunY = (int) ($horizon * ($pano ? 0.55 : 0.35));
        for ($r = 90; $r > 0; $r -= 6) {
            $a = (int) (127 - (90 - $r) * 1.2);
            imagefilledellipse($img, $sunX, $sunY, $r * 2, $r * 2, imagecolorallocatealpha($img, 255, 240, 200, max(0, min(127, $a))));
        }

        // Clouds
        for ($i = 0; $i < ($pano ? 14 : 6); $i++) {
            $cx = mt_rand(0, $w);
            $cy = mt_rand((int) ($horizon * 0.15), (int) ($horizon * 0.7));
            for ($j = 0; $j < 6; $j++) {
                $this->wrapEllipse($img, $cx + mt_rand(-60, 60), $cy + mt_rand(-12, 12), mt_rand(60, 140), mt_rand(24, 44), imagecolorallocatealpha($img, 255, 255, 255, 70), $w);
            }
        }

        // Ground gradient
        $this->gradient($img, $horizon, $h, $w, $t['ground'][0], $t['ground'][1]);

        // Distant & near hills (periodic so the panorama seam matches)
        $this->ridge($img, $w, $horizon, $t['far'], $t['feature'] === 'peaks' || $t['feature'] === 'volcano' ? 0.22 : 0.08, $pano ? $h : $h * 1.6, $seed);
        if ($t['feature'] === 'volcano') {
            $this->volcano($img, $w, $horizon, $h, $t['far']);
        }
        $this->ridge($img, $w, $horizon, $t['near'], 0.05, $pano ? $h : $h * 1.6, $seed + 7, 0.02);

        match ($t['feature']) {
            'trees' => $this->trees($img, $w, $horizon, $h, 160),
            'acacia' => $this->acacias($img, $w, $horizon, $h),
            'water' => $this->water($img, $w, $horizon, $h),
            'peaks' => $this->snow($img, $w, $horizon, $h, $seed),
            default => null,
        };

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        imagejpeg($img, $path, 82);
        imagedestroy($img);
    }

    private function gradient($img, int $y0, int $y1, int $w, array $c0, array $c1): void
    {
        $span = max(1, $y1 - $y0);
        for ($y = $y0; $y < $y1; $y++) {
            $k = ($y - $y0) / $span;
            $col = imagecolorallocate($img,
                (int) ($c0[0] + ($c1[0] - $c0[0]) * $k),
                (int) ($c0[1] + ($c1[1] - $c0[1]) * $k),
                (int) ($c0[2] + ($c1[2] - $c0[2]) * $k));
            imageline($img, 0, $y, $w, $y, $col);
        }
    }

    private function ridge($img, int $w, int $horizon, array $color, float $amp, float $h, int $seed, float $offset = 0.0): void
    {
        mt_srand($seed);
        $waves = [];
        for ($i = 1; $i <= 5; $i++) {
            $waves[] = [mt_rand(1, 3) * $i, mt_rand(0, 628) / 100, $amp / $i];
        }
        $col = imagecolorallocate($img, ...$color);
        for ($x = 0; $x < $w; $x++) {
            $v = 0;
            foreach ($waves as [$f, $p, $a]) {
                $v += sin(2 * M_PI * $f * $x / $w + $p) * $a;
            }
            $top = (int) ($horizon - (abs($v) + $offset) * $h * 0.5) - 4;
            imageline($img, $x, $top, $x, $horizon + 2, $col);
        }
    }

    private function volcano($img, int $w, int $horizon, int $h, array $color): void
    {
        foreach ([0.2, 0.55, 0.8] as $i => $pos) {
            $cx = (int) ($w * $pos);
            $peak = (int) ($horizon - $h * (0.16 + 0.04 * $i));
            $base = (int) ($w * 0.09);
            imagefilledpolygon($img, [$cx - $base, $horizon, $cx - 14, $peak, $cx + 14, $peak, $cx + $base, $horizon], imagecolorallocate($img, $color[0] - 20, $color[1] - 20, $color[2] - 20));
        }
    }

    private function snow($img, int $w, int $horizon, int $h, int $seed): void
    {
        mt_srand($seed + 3);
        $white = imagecolorallocatealpha($img, 245, 248, 255, 20);
        for ($i = 0; $i < 4; $i++) {
            $cx = mt_rand(0, $w);
            $peak = (int) ($horizon - $h * 0.2);
            imagefilledpolygon($img, [$cx - 60, $peak + 50, $cx, $peak - 10, $cx + 60, $peak + 50], $white);
        }
    }

    private function trees($img, int $w, int $horizon, int $h, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $x = mt_rand(0, $w);
            $y = $horizon + mt_rand(-10, (int) (($h - $horizon) * 0.6));
            $s = (int) (18 + ($y - $horizon) * 0.35);
            $g = mt_rand(40, 90);
            $col = imagecolorallocate($img, (int) ($g * 0.4), $g, (int) ($g * 0.5));
            imagefilledrectangle($img, $x - 2, $y, $x + 2, $y + $s, imagecolorallocate($img, 60, 40, 25));
            $this->wrapEllipse($img, $x, $y, $s * 2, (int) ($s * 1.6), $col, $w);
        }
    }

    private function acacias($img, int $w, int $horizon, int $h): void
    {
        $dark = imagecolorallocate($img, 45, 40, 25);
        for ($i = 0; $i < 12; $i++) {
            $x = mt_rand(0, $w);
            $y = $horizon + mt_rand(8, (int) (($h - $horizon) * 0.45));
            $s = (int) (14 + ($y - $horizon) * 0.45);
            imagesetthickness($img, max(2, intdiv($s, 12)));
            imageline($img, $x, $y, $x, $y - $s, $dark);
            imageline($img, $x, $y - (int) ($s * 0.6), $x - (int) ($s * 0.35), $y - $s, $dark);
            imageline($img, $x, $y - (int) ($s * 0.6), $x + (int) ($s * 0.35), $y - $s, $dark);
            imagesetthickness($img, 1);
            $this->wrapEllipse($img, $x, $y - $s, (int) ($s * 1.5), max(6, (int) ($s * 0.28)), $dark, $w);
        }
        // Grass strokes
        for ($i = 0; $i < 900; $i++) {
            $x = mt_rand(0, $w);
            $y = mt_rand($horizon + 5, $h);
            imageline($img, $x, $y, $x + mt_rand(-3, 3), $y - mt_rand(4, 12), imagecolorallocatealpha($img, 90, 70, 30, 60));
        }
    }

    private function water($img, int $w, int $horizon, int $h): void
    {
        for ($i = 0; $i < 400; $i++) {
            $x = mt_rand(0, $w);
            $y = mt_rand($horizon + 4, $h);
            $len = mt_rand(10, 60);
            imageline($img, $x, $y, $x + $len, $y, imagecolorallocatealpha($img, 230, 240, 255, mt_rand(60, 110)));
        }
        // Reeds / shoreline trees
        $this->trees($img, $w, $horizon - 4, $horizon + 20, 50);
    }

    /** Ellipse that wraps across the left/right edge for seamless panoramas. */
    private function wrapEllipse($img, int $x, int $y, int $ew, int $eh, int $col, int $w): void
    {
        imagefilledellipse($img, $x, $y, $ew, $eh, $col);
        if ($x - $ew / 2 < 0) {
            imagefilledellipse($img, $x + $w, $y, $ew, $eh, $col);
        }
        if ($x + $ew / 2 > $w) {
            imagefilledellipse($img, $x - $w, $y, $ew, $eh, $col);
        }
    }
}
