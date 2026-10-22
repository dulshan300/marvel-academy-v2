<?php

namespace Database\Seeders\Support;

use GdImage;

/**
 * Draws simple line graphs and bar charts as PNGs with GD for demo picture
 * questions. Uses a system TrueType font when one is available, otherwise
 * GD's built-in bitmap font, so seeding needs no extra packages.
 */
class DemoChart
{
    private const WIDTH = 900;

    private const HEIGHT = 600;

    private const LEFT = 100;

    private const RIGHT = 40;

    private const TOP = 70;

    private const BOTTOM = 90;

    /** Candidate fonts: Windows, Debian/Ubuntu, Alpine, macOS. */
    private const FONTS = [
        'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/Library/Fonts/Arial.ttf',
    ];

    private GdImage $image;

    private ?string $font;

    /** @var array<string, int> */
    private array $colours = [];

    private function __construct(string $title)
    {
        $this->image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imageantialias($this->image, true);
        $this->font = collect(self::FONTS)->first(fn (string $path) => is_file($path));

        foreach ([
            'white' => [255, 255, 255],
            'ink' => [30, 41, 59],
            'muted' => [100, 116, 139],
            'grid' => [226, 232, 240],
            'brand' => [10, 99, 117],
            'accent' => [255, 137, 36],
        ] as $name => [$red, $green, $blue]) {
            $this->colours[$name] = imagecolorallocate($this->image, $red, $green, $blue);
        }

        imagefill($this->image, 0, 0, $this->colours['white']);
        $this->text($title, self::WIDTH / 2, 40, 20, 'ink', centred: true);
    }

    /**
     * A line graph through the given points, with each point marked.
     *
     * @param  array<int, array{0: float|int, 1: float|int}>  $points
     */
    public static function lineGraph(string $title, string $xLabel, string $yLabel, int $xMax, int $yMax, int $xStep, int $yStep, array $points): string
    {
        $chart = new self($title);
        $chart->axes($xLabel, $yLabel, $xMax, $yMax, $xStep, $yStep);

        foreach (array_slice($points, 1) as $index => $point) {
            $previous = $points[$index];
            $chart->thickLine(
                $chart->x($previous[0], $xMax), $chart->y($previous[1], $yMax),
                $chart->x($point[0], $xMax), $chart->y($point[1], $yMax),
                5, 'brand',
            );
        }

        foreach ($points as [$x, $y]) {
            imagefilledellipse($chart->image, $chart->x($x, $xMax), $chart->y($y, $yMax), 16, 16, $chart->colours['accent']);
        }

        return $chart->png();
    }

    /**
     * A vertical bar chart with the value printed above each bar.
     *
     * @param  array<string, int>  $bars  label => value
     */
    public static function barChart(string $title, string $xLabel, string $yLabel, int $yMax, int $yStep, array $bars): string
    {
        $chart = new self($title);
        $chart->axes($xLabel, $yLabel, 0, $yMax, 0, $yStep);

        $plotWidth = self::WIDTH - self::LEFT - self::RIGHT;
        $slot = $plotWidth / count($bars);
        $barWidth = (int) ($slot * 0.55);
        $index = 0;

        foreach ($bars as $label => $value) {
            $centre = (int) (self::LEFT + $slot * ($index + 0.5));
            $top = $chart->y($value, $yMax);
            imagefilledrectangle($chart->image, $centre - intdiv($barWidth, 2), $top, $centre + intdiv($barWidth, 2), $chart->y(0, $yMax) - 1, $chart->colours['brand']);
            $chart->text((string) $value, $centre, $top - 12, 16, 'ink', centred: true);
            $chart->text($label, $centre, self::HEIGHT - self::BOTTOM + 30, 15, 'ink', centred: true);
            $index++;
        }

        return $chart->png();
    }

    /**
     * Gridlines, axes and labels. An xMax of 0 skips the x scale (bar charts).
     */
    private function axes(string $xLabel, string $yLabel, int $xMax, int $yMax, int $xStep, int $yStep): void
    {
        $left = self::LEFT;
        $right = self::WIDTH - self::RIGHT;
        $bottom = self::HEIGHT - self::BOTTOM;

        for ($value = 0; $value <= $yMax; $value += $yStep) {
            $y = $this->y($value, $yMax);
            imageline($this->image, $left, $y, $right, $y, $this->colours['grid']);
            $this->text((string) $value, $left - 14, $y + 6, 14, 'muted', alignRight: true);
        }

        if ($xMax > 0) {
            for ($value = 0; $value <= $xMax; $value += $xStep) {
                $x = $this->x($value, $xMax);
                imageline($this->image, $x, self::TOP, $x, $bottom, $this->colours['grid']);
                $this->text((string) $value, $x, $bottom + 28, 14, 'muted', centred: true);
            }
        }

        $this->thickLine($left, self::TOP, $left, $bottom, 3, 'ink');
        $this->thickLine($left, $bottom, $right, $bottom, 3, 'ink');

        $this->text($xLabel, ($left + $right) / 2, self::HEIGHT - 22, 16, 'ink', centred: true);
        $this->text($yLabel, 30, ($bottom + self::TOP) / 2, 16, 'ink', vertical: true);
    }

    /**
     * A line of the given width drawn as a filled polygon, because GD ignores
     * line thickness while antialiasing is on.
     */
    private function thickLine(int $x1, int $y1, int $x2, int $y2, int $width, string $colour): void
    {
        $length = max(1.0, hypot($x2 - $x1, $y2 - $y1));
        $offsetX = ($y2 - $y1) / $length * $width / 2;
        $offsetY = ($x1 - $x2) / $length * $width / 2;

        imagefilledpolygon($this->image, [
            (int) round($x1 + $offsetX), (int) round($y1 + $offsetY),
            (int) round($x2 + $offsetX), (int) round($y2 + $offsetY),
            (int) round($x2 - $offsetX), (int) round($y2 - $offsetY),
            (int) round($x1 - $offsetX), (int) round($y1 - $offsetY),
        ], $this->colours[$colour]);
    }

    private function x(float|int $value, int $max): int
    {
        return (int) round(self::LEFT + $value / $max * (self::WIDTH - self::LEFT - self::RIGHT));
    }

    private function y(float|int $value, int $max): int
    {
        return (int) round(self::HEIGHT - self::BOTTOM - $value / $max * (self::HEIGHT - self::TOP - self::BOTTOM));
    }

    private function text(string $text, float|int $x, float|int $y, int $size, string $colour, bool $centred = false, bool $alignRight = false, bool $vertical = false): void
    {
        if ($this->font === null) {
            $width = imagefontwidth(5) * strlen($text);

            if ($vertical) {
                imagestringup($this->image, 5, (int) $x - 8, (int) ($y + $width / 2), $text, $this->colours[$colour]);

                return;
            }

            $left = $centred ? $x - $width / 2 : ($alignRight ? $x - $width : $x);
            imagestring($this->image, 5, (int) $left, (int) $y - 12, $text, $this->colours[$colour]);

            return;
        }

        $box = imagettfbbox($size, 0, $this->font, $text);
        $width = $box[2] - $box[0];

        if ($vertical) {
            imagettftext($this->image, $size, 90, (int) $x + 6, (int) ($y + $width / 2), $this->colours[$colour], $this->font, $text);

            return;
        }

        $left = $centred ? $x - $width / 2 : ($alignRight ? $x - $width : $x);
        imagettftext($this->image, $size, 0, (int) $left, (int) $y, $this->colours[$colour], $this->font, $text);
    }

    private function png(): string
    {
        ob_start();
        imagepng($this->image, null, 6);

        return (string) ob_get_clean();
    }
}
