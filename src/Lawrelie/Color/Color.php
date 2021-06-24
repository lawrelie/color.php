<?php
namespace Lawrelie\Color;
use Throwable;
class Color {
    private array $color;
    private array $readableProperties = [];
    public function __construct(string $color) {
        $color = \preg_replace('/[^0-9a-z(\.%,)]+/iu', '', \mb_convert_kana($color, 'as'));
        if (!!\preg_match('/^#?(.{2})(.{2})(.{2})$/u', $color, $m)) {
            $this->setFromHexTripet($m[1], $m[2], $m[3]);
        } elseif (!!\preg_match('/^#?(.)(.)(.)$/u', $color, $m)) {
            $this->setFromHexTripet(\str_repeat($m[1], 2), \str_repeat($m[2], 2), \str_repeat($m[3], 2));
        } elseif (!!\preg_match('/^hsla?\(([0-9]+),([0-9]+)%?,([0-9]+)%?(?:,[0-9]+(?:\.[0-9]+)?%?)?\)$/iu', $color, $m)) {
            $this->setFromHsl($m[1], $m[2], $m[3]);
        } elseif (!!\preg_match('/^hsva?\(([0-9]+),([0-9]+)%?,([0-9]+)%?(?:,[0-9]+(?:\.[0-9]+)?%?)?\)$/iu', $color, $m)) {
            $this->setFromHsv($m[1], $m[2], $m[3]);
        } elseif (!!\preg_match('/^rgba?\(([0-9]+),([0-9]+),([0-9]+)(?:,[0-9]+(?:\.[0-9]+)?%?)?\)$/iu', $color, $m)) {
            $this->setFromRgb($m[1], $m[2], $m[3]);
        } else {
            throw new \DomainException();
        }
    }
    public function __get(string $name) {
        if (\array_key_exists($name, $this->readableProperties)) {
            return $this->readableProperties[$name];
        }
        $this->readableProperties[$name] = null;
        try {
            $this->readableProperties[$name] = [$this, 'get_' . $name]();
        } catch (Throwable $e) {}
        return $this->readableProperties[$name];
    }
    public function create(string $color, string $className = null): self {
        $className = !\is_null($className) ? $className : static::class;
        return new $className($color);
    }
    public function decToHex(float $dec): string {
        return \str_pad(\dechex(\round($dec)), 2, '0', \STR_PAD_LEFT);
    }
    protected function get_hexTripet(): string {
        return '#' . $this->color['hexTripet'];
    }
    protected function get_hsl(): array {
        list($h, $s, $l) = $this->color['hsl'];
        return [\round($h), \round($s), \round($l)];
    }
    protected function get_hslString(): string {
        list($h, $s, $l) = $this->hsl;
        return "hsl($h, $s%, $l%)";
    }
    protected function get_hsv(): array {
        list($h, $s, $v) = $this->color['hsv'];
        return [\round($h), \round($s), \round($v)];
    }
    protected function get_hsvString(): string {
        list($h, $s, $v) = $this->hsv;
        return "hsv($h, $s%, $v%)";
    }
    protected function get_isDark(): bool {
        return !$this->isLight;
    }
    protected function get_isLight(): bool {
        $l = $this->relativeLuminanceFromRgb(...$this->rgb);
        return 0 > ($this->relativeLuminanceFromRgb(255, 255, 255) + 0.05) / ($l + 0.05) - ($l + 0.05) / ($this->relativeLuminanceFromRgb(0, 0, 0) + 0.05);
    }
    protected function get_rgb(): array {
        list($r, $g, $b) = $this->color['rgb'];
        return [\round($r), \round($g), \round($b)];
    }
    protected function get_rgbString(): string {
        return 'rgb(' . \implode(', ', $this->rgb) . ')';
    }
    protected function get_textColor(): self {
        return $this->create('hsl(0, 0%, ' . (!$this->isLight ? 100 : 0) . '%)');
    }
    public function hsbNumberToPercentage(float $number): float {
        return $number * 100;
    }
    public function hsbPercentageToNumber(float $percentage): float {
        return $percentage / 100;
    }
    public function hsbToRgb(float $hue, float $chroma, float $match): array {
        $hue_ = $hue / 60;
        $x = $chroma * (1 - ($hue_ % 2 - 1));
        if (0 > $hue_ || 6 < $hue_) {
            $r1 = 0;
            $g1 = 0;
            $b1 = 0;
        } elseif (1 >= $hue_) {
            $r1 = $chroma;
            $g1 = $x;
            $b1 = 0;
        } elseif (2 >= $hue_) {
            $r1 = $x;
            $g1 = $chroma;
            $b1 = 0;
        } elseif (3 >= $hue_) {
            $r1 = 0;
            $g1 = $chroma;
            $b1 = $x;
        } elseif (4 >= $hue_) {
            $r1 = 0;
            $g1 = $x;
            $b1 = $chroma;
        } elseif (5 >= $hue_) {
            $r1 = $x;
            $g1 = 0;
            $b1 = $chroma;
        } else {
            $r1 = $chroma;
            $g1 = 0;
            $b1 = $x;
        }
        return [$this->rgbSTo8bit($r1 + $match), $this->rgbSTo8bit($g1 + $match), $this->rgbSTo8bit($b1 + $match)];
    }
    public function rgb8bitToS(float $rgb8): float {
        return $rgb8 / 255;
    }
    public function rgbSTo8bit(float $rgbs): float {
        return $rgbs * 255;
    }
    public function rgbToHexTripet(float $r8, float $g8, float $b8): string {
        return $this->decToHex($r8) . $this->decToHex($g8) . $this->decToHex($b8);
    }
    public function relativeLuminanceFromRgb(float $r8, float $g8, float $b8): float {
        $rs = $this->rgb8bitToS($r8);
        $gs = $this->rgb8bitToS($g8);
        $bs = $this->rgb8bitToS($b8);
        return
            0.2126 * (0.03928 >= $rs ? $rs / 12.92 : (($rs + 0.055) / 1.055) ** 2.4)
            + 0.7152 * (0.03928 >= $gs ? $gs / 12.92 : (($gs + 0.055) / 1.055) ** 2.4)
            + 0.0722 * (0.03928 >= $bs ? $bs / 12.92 : (($bs + 0.055) / 1.055) ** 2.4);
    }
    protected function setFromHexTripet(string $r, string $g, string $b): void {
        $this->setFromRgb(\hexdec($r), \hexdec($g), \hexdec($b));
    }
    protected function setFromHsl(int $h, int $sp, int $lp): void {
        $ln = $this->hsbPercentageToNumber($lp);
        $sn = $this->hsbPercentageToNumber($sp);
        $chroma = (1 - (2 * $ln)) * $sn;
        $rgb = $this->hsbToRgb($h, $chroma, $ln - $chroma / 2);
        $value = $ln + $sn * \min($ln, 1 - $ln);
        $this->color = [
            'hexTripet' => $this->rgbToHexTripet(...$rgb),
            'hsl' => [$h, $sp, $lp],
            'hsv' => [$h, $this->hsbNumberToPercentage(!$value ? 0 : 2 * (1 - $ln / $value)), $this->hsbNumberToPercentage($value)],
            'rgb' => $rgb,
       ];
    }
    protected function setFromHsv(int $h, int $sp, int $vp): void {
        $sn = $this->hsbPercentageToNumber($sp);
        $vn = $this->hsbPercentageToNumber($vp);
        $chroma = $vn * $sn;
        $lightness = $vn * (1 - $sn / 2);
        $rgb = $this->hsbToRgb($h, $chroma, $vn - $chroma);
        $this->color = [
            'hexTripet' => $this->rgbToHexTripet(...$rgb),
            'hsl' => [$h, $this->hsbNumberToPercentage(!$lightness || 1 === $lightness ? 0 : ($vn - $lightness) / \min($lightness, 1 - $lightness)), $this->hsbNumberToPercentage($lightness)],
            'hsv' => [$h, $sp, $vp],
            'rgb' => $rgb,
       ];
    }
    protected function setFromRgb(int $r8, int $g8, int $b8): void {
        $rs = $this->rgb8bitToS($r8);
        $gs = $this->rgb8bitToS($g8);
        $bs = $this->rgb8bitToS($b8);
        $rgbsMax = $value = \max($rs, $gs, $bs);
        $rgbsMin = \min($rs, $gs, $bs);
        $rgbsMid = $lightness = ($rgbsMax + $rgbsMin) / 2;
        $chroma = $rgbsMax - $rgbsMin;
        $hue = !$chroma ? 0 : 60 * ($rs === $value ? 0 + ($gs - $bs) / $chroma : ($gs === $value ? 2 + ($bs - $rs) / $chroma : 4 + ($rs - $gs) / $chroma));
        $hue += 0 > $hue ? 360: 0;
        $lightnessSaturation = !$lightness || 1.0 === $lightness ? 0 : ($value - $lightness) / \min($lightness, 1 - $lightness);
        $valueSaturation = !$value ? 0 : $chroma / $value;
        $this->color = [
            'hexTripet' => $this->rgbToHexTripet($r8, $g8, $b8),
            'hsl' => [$hue, $this->hsbNumberToPercentage($lightnessSaturation), $this->hsbNumberToPercentage($lightness)],
            'hsv' => [$hue, $this->hsbNumberToPercentage($valueSaturation), $this->hsbNumberToPercentage($value)],
            'rgb' => [$r8, $g8, $b8],
       ];
    }
}
