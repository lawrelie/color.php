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
    protected function setFromHsl(int $hue, int $saturation, int $lightness): void {
        $h = $hue;
        $sl = $saturation / 100;
        $l = $lightness / 100;
        // $c = (1 - (2 * $l - 1)) * $sl;
        $h_ = $h / 60;
        // $x = $c * (1 - (fmod($h_, 2) - 1));
        // [$r1, $g1, $b1] = match (true) {
        //     0 > $h_, 6 <= $h_ => [0, 0, 0],
        //     1 > $h_ => [$c, $x, 0],
        //     2 > $h_ => [$x, $c, 0],
        //     3 > $h_ => [0, $c, $x],
        //     4 > $h_ => [0, $x, $c],
        //     5 > $h_ => [$x, 0, $c],
        //     default => [$c, 0, $x],
        // };
        // $m = $l - $c / 2;
        // $rgb = array_map(function(float $rgb) use($m): float {return ($rgb + $m) * 255;}, [$r1, $g1, $b1]);
        $hv = $hl = $hue;
        $v = $l + $sl * min($l, 1 - $l);
        $sv = 0 == $v ? 0 : 2 * (1 - $l / $v);

        // From: from RGB
        $c = 0 === $h ? 0 : 2 * ($v - $l);
        $xmin = $v - $c;
        $xmax = $v;
        if (0 == $h_ || 0 == $c) {
            $r = $g = $b = $xmax;
        } else if (1 >= $h_) {
            $r = $v;
            $gminusb = ($h_ - 0) * $c;
            if (0 > $gminusb) {
                $g = $xmin;
                $b = $g - $gminusb;
            } else {
                $b = $xmin;
                $g = $gminusb + $b;
            }
        } else if (3 >= $h_) {
            $g = $v;
            $bminusr = ($h_ - 2) * $c;
            if (0 > $bminusr) {
                $b = $xmin;
                $r = $b - $bminusr;
            } else {
                $r = $xmin;
                $b = $bminusr + $r;
            }
        } else if (5 >= $h_) {
            $b = $v;
            $rminusg = ($h_ - 4) * $c;
            if (0 > $rminusg) {
                $r = $xmin;
                $g = $r - $rminusg;
            } else {
                $g = $xmin;
                $r = $rminusg + $g;
            }
        }
        $rgb = array_map(fn(float $rgb): float => $rgb * 255, [$r, $g, $b]);

        $this->color = [
            'hexTripet' => $this->rgbToHexTripet(...$rgb),
            'hsl' => [$hue, $saturation, $lightness],
            'hsv' => [$hue, $sv * 100, $v * 100],
            'rgb' => $rgb,
       ];
    }
    protected function setFromHsv(int $h, int $s, int $v): void {
        $v_ = $v / 100;
        $lightness = $v_ * (1 - $s / 100 / 2);
        $this->setFromHsl($h, (!$lightness || 1 === $lightness ? 0 : ($v_ - $lightness) / \min($lightness, 1 - $lightness)) * 100, $lightness * 100);
    }
    protected function setFromRgb(int $red, int $green, int $blue): void {
        $r = $red / 255;
        $g = $green / 255;
        $b = $blue / 255;
        $xmax = $v = max($r, $g, $b);
        $xmin = min($r, $g, $b);
        $c = $xmax - $xmin;
        $l = $v - $c / 2;
        $h = 0 == $c ? 0 : 60 * match (true) {
            $r === $v => 0 + ($g - $b) / $c,
            $g === $v => 2 + ($b - $r) / $c,
            $b === $v => 4 + ($r - $g) / $c,
        };
        $this->color = [
            'hexTripet' => $this->rgbToHexTripet($red, $green, $blue),
            'hsl' => [$h, (0 == $l || 1 == $l ? 0 : ($v - $l) / min($l, 1 - $l)) * 100, $l * 100],
            'hsv' => [$h, (0 == $v ? 0 : $c / $v) * 100, $v * 100],
            'rgb' => [$red, $green, $blue],
       ];
    }
}
