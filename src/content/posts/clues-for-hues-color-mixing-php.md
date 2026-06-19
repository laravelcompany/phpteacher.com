---
title: "Clues for Hues - Color Mixing and Manipulation in PHP"
description: "Learn how to work with colors in PHP. From hex codes to RGB, HSL, color mixing algorithms, and building a color palette generator with PHP."
pubDate: "2022-06-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Colors", "RGB", "HSL", "Color Theory", "Graphics", "Image Processing"]
selected: false
---

Color is everywhere in web development. The button you just styled, the chart you rendered, the image you processed — every pixel involves a color representation and often some transformation between formats.

PHP isn't the first language people think of for color manipulation, but it handles the job surprisingly well. Whether you're generating CSS variables dynamically, building a data visualization library, or processing images with GD, understanding color math in PHP pays off.

## How PHP Represents Colors

Colors in PHP come in three dominant representations: hex strings, RGB arrays, and HSL values. Each serves a different purpose.

### Hex Colors

The most familiar format for web developers. A hex color like `#FF6633` breaks down into red (`FF`), green (`66`), and blue (`33`) components.

```php
$hex = '#FF6633';

// Extract components
$red   = hexdec(substr($hex, 1, 2)); // 255
$green = hexdec(substr($hex, 3, 2)); // 102
$blue  = hexdec(substr($hex, 5, 2)); // 51
```

Short hex notation (`#F63`) expands by doubling each character:

```php
function expandHex(string $shortHex): string
{
    $r = $shortHex[1];
    $g = $shortHex[2];
    $b = $shortHex[3];

    return "#{$r}{$r}{$g}{$g}{$b}{$b}";
}

expandHex('#F63'); // '#FF6633'
```

### RGB Colors

RGB values range from 0 to 255 for each channel. PHP doesn't have a built-in RGB type, but an associative array works well:

```php
$rgb = ['r' => 255, 'g' => 102, 'b' => 51];
```

### HSL Colors

HSL (Hue, Saturation, Lightness) is more intuitive for humans. Hue is an angle from 0 to 360 on the color wheel. Saturation and lightness are percentages.

```php
$hsl = ['h' => 15, 's' => 100, 'l' => 60];
```

HSL makes it easy to reason about color relationships. "Make this color darker" means lowering lightness. "Make it more vibrant" means raising saturation. With RGB, those operations are not straightforward.

## Converting Between Color Spaces

Every color conversion involves math. Here are the conversions you'll use most often.

### Hex to RGB

```php
function hexToRgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2)),
    ];
}
```

### RGB to Hex

```php
function rgbToHex(int $r, int $g, int $b): string
{
    return sprintf('#%02X%02X%02X', $r, $g, $b);
}

rgbToHex(255, 102, 51); // '#FF6633'
```

### RGB to HSL

This is the trickiest conversion. HSL models color as a cylinder rather than a cube, and the math reflects that.

```php
function rgbToHsl(int $r, int $g, int $b): array
{
    $r /= 255;
    $g /= 255;
    $b /= 255;

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $delta = $max - $min;

    $l = ($max + $min) / 2;

    if ($delta === 0.0) {
        return ['h' => 0, 's' => 0, 'l' => round($l * 100)];
    }

    $s = $l > 0.5
        ? $delta / (2 - $max - $min)
        : $delta / ($max + $min);

    $h = match ($max) {
        $r => 60 * fmod((($g - $b) / $delta), 6),
        $g => 60 * ((($b - $r) / $delta) + 2),
        $b => 60 * ((($r - $g) / $delta) + 4),
    };

    if ($h < 0) {
        $h += 360;
    }

    return [
        'h' => round($h),
        's' => round($s * 100),
        'l' => round($l * 100),
    ];
}
```

### HSL to RGB

The reverse conversion is equally involved:

```php
function hslToRgb(int $h, int $s, int $l): array
{
    $s /= 100;
    $l /= 100;

    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;

    $segment = floor($h / 60);

    $rgb = match ($segment) {
        0 => [$c, $x, 0],
        1 => [$x, $c, 0],
        2 => [0, $c, $x],
        3 => [0, $x, $c],
        4 => [$x, 0, $c],
        5 => [$c, 0, $x],
    };

    return array_map(fn($v) => round(($v + $m) * 255), $rgb);
}
```

With these four functions, you can move between any color space your application needs.

## Color Mixing: Additive vs Subtractive

Mixing colors works differently depending on the medium. This distinction matters when your PHP application deals with both screens and print.

### Additive Mixing (RGB — Screens)

Screens emit light. Red + Green + Blue at full intensity equals white. This is additive mixing — combining light wavelengths.

```php
function mixAdditive(array $color1, array $color2): array
{
    return [
        'r' => min(255, $color1['r'] + $color2['r']),
        'g' => min(255, $color1['g'] + $color2['g']),
        'b' => min(255, $color1['b'] + $color2['b']),
    ];
}
```

More practically, you'll often want weighted blending (alpha compositing):

```php
function blendColors(array $foreground, array $background, float $alpha): array
{
    return [
        'r' => round($foreground['r'] * $alpha + $background['r'] * (1 - $alpha)),
        'g' => round($foreground['g'] * $alpha + $background['g'] * (1 - $alpha)),
        'b' => round($foreground['b'] * $alpha + $background['b'] * (1 - $alpha)),
    ];
}
```

### Subtractive Mixing (CMYK — Print)

Print works by absorbing light. Cyan + Magenta + Yellow = muddy brown, approaching black. This is subtractive mixing — each pigment absorbs wavelengths.

```php
function rgbToCmyk(int $r, int $g, int $b): array
{
    $r /= 255;
    $g /= 255;
    $b /= 255;

    $k = 1 - max($r, $g, $b);

    if ($k === 1.0) {
        return ['c' => 0, 'm' => 0, 'y' => 0, 'k' => 100];
    }

    return [
        'c' => round((1 - $r - $k) / (1 - $k) * 100),
        'm' => round((1 - $g - $k) / (1 - $k) * 100),
        'y' => round((1 - $b - $k) / (1 - $k) * 100),
        'k' => round($k * 100),
    ];
}
```

For most PHP work, additive mixing is what you need. Screens dominate web development.

## Calculating Color Harmonies

Color theory defines relationships between colors. These relationships produce pleasing palettes. With HSL, computing them is straightforward.

### Complementary Colors

Complementary colors sit opposite each other on the color wheel (180 degrees apart).

```php
function complementary(array $hsl): array
{
    return [
        'h' => ($hsl['h'] + 180) % 360,
        's' => $hsl['s'],
        'l' => $hsl['l'],
    ];
}
```

### Analogous Colors

Analogous colors sit next to each other (30 degrees apart).

```php
function analogous(array $hsl): array
{
    return [
        [
            'h' => ($hsl['h'] - 30 + 360) % 360,
            's' => $hsl['s'],
            'l' => $hsl['l'],
        ],
        $hsl,
        [
            'h' => ($hsl['h'] + 30) % 360,
            's' => $hsl['s'],
            'l' => $hsl['l'],
        ],
    ];
}
```

### Triadic Colors

Triadic colors are evenly spaced around the wheel (120 degrees apart).

```php
function triadic(array $hsl): array
{
    return [
        $hsl,
        [
            'h' => ($hsl['h'] + 120) % 360,
            's' => $hsl['s'],
            'l' => $hsl['l'],
        ],
        [
            'h' => ($hsl['h'] + 240) % 360,
            's' => $hsl['s'],
            'l' => $hsl['l'],
        ],
    ];
}
```

### Split Complementary

A base color plus the two colors adjacent to its complement.

```php
function splitComplementary(array $hsl): array
{
    $complementHue = ($hsl['h'] + 180) % 360;

    return [
        $hsl,
        [
            'h' => ($complementHue - 30 + 360) % 360,
            's' => $hsl['s'],
            'l' => $hsl['l'],
        ],
        [
            'h' => ($complementHue + 30) % 360,
            's' => $hsl['s'],
            'l' => $hsl['l'],
        ],
    ];
}
```

## Building a Palette Generator

With these building blocks, you can assemble a palette generator that creates harmonious color schemes from a single starting color.

```php
class PaletteGenerator
{
    public function __construct(
        private string $baseHex
    ) {}

    public function generate(string $scheme = 'analogous'): array
    {
        $hsl = rgbToHsl(...array_values(hexToRgb($this->baseHex)));

        $harmonies = match ($scheme) {
            'complementary'     => [$hsl, complementary($hsl)],
            'analogous'         => analogous($hsl),
            'triadic'           => triadic($hsl),
            'split-complementary' => splitComplementary($hsl),
            default             => [$hsl],
        };

        return array_map(function ($color) {
            $rgb = hslToRgb($color['h'], $color['s'], $color['l']);
            return rgbToHex($rgb[0], $rgb[1], $rgb[2]);
        }, $harmonies);
    }

    public function generateShades(int $count = 5): array
    {
        $hsl = rgbToHsl(...array_values(hexToRgb($this->baseHex)));
        $shades = [];

        for ($i = 0; $i < $count; $i++) {
            $lightness = max(5, min(95, $hsl['l'] + ($i - floor($count / 2)) * 15));
            $shades[] = rgbToHex(...hslToRgb($hsl['h'], $hsl['s'], (int) $lightness));
        }

        return $shades;
    }
}

$palette = new PaletteGenerator('#3366FF');
print_r($palette->generate('triadic'));
print_r($palette->generateShades(7));
```

## Working With Images Using GD

PHP's GD library provides image creation and manipulation. Color functions are central to working with it.

```php
// Create a 400x400 image
$image = imagecreatetruecolor(400, 400);

// Allocate colors
$red   = imagecolorallocate($image, 255, 0, 0);
$green = imagecolorallocate($image, 0, 255, 0);
$blue  = imagecolorallocate($image, 0, 0, 255);

// Use the colors
imagefilledrectangle($image, 50, 50, 150, 150, $red);
imagefilledellipse($image, 200, 200, 100, 100, $green);

// Output as PNG
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
```

For more control, allocate colors dynamically from hex:

```php
function allocateHex(GdImage $image, string $hex): int
{
    $rgb = hexToRgb($hex);
    return imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
}

$image = imagecreatetruecolor(800, 600);
$bgColor = allocateHex($image, '#2C3E50');
$fgColor = allocateHex($image, '#E74C3C');

imagefilledrectangle($image, 0, 0, 800, 600, $bgColor);
imagefilledrectangle($image, 100, 100, 700, 500, $fgColor);
```

### Generating a Gradient

Gradients interpolate between two colors. Here's a linear gradient using RGB interpolation:

```php
function gradientImage(int $width, int $height, string $hex1, string $hex2): GdImage
{
    $image = imagecreatetruecolor($width, $height);
    $rgb1 = hexToRgb($hex1);
    $rgb2 = hexToRgb($hex2);

    for ($x = 0; $x < $width; $x++) {
        $ratio = $x / $width;
        $r = (int) round($rgb1['r'] + ($rgb2['r'] - $rgb1['r']) * $ratio);
        $g = (int) round($rgb1['g'] + ($rgb2['g'] - $rgb1['g']) * $ratio);
        $b = (int) round($rgb1['b'] + ($rgb2['b'] - $rgb1['b']) * $ratio);

        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, $x, 0, $x, $height, $color);
    }

    return $image;
}
```

HSL interpolation produces smoother gradients that pass through more natural intermediate colors:

```php
function gradientImageHsl(int $width, int $height, string $hex1, string $hex2): GdImage
{
    $image = imagecreatetruecolor($width, $height);
    $hsl1 = rgbToHsl(...array_values(hexToRgb($hex1)));
    $hsl2 = rgbToHsl(...array_values(hexToRgb($hex2)));

    for ($x = 0; $x < $width; $x++) {
        $ratio = $x / $width;
        $h = $hsl1['h'] + ($hsl2['h'] - $hsl1['h']) * $ratio;
        $s = $hsl1['s'] + ($hsl2['s'] - $hsl1['s']) * $ratio;
        $l = $hsl1['l'] + ($hsl2['l'] - $hsl1['l']) * $ratio;

        $rgb = hslToRgb((int) round($h), (int) round($s), (int) round($l));
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imageline($image, $x, 0, $x, $height, $color);
    }

    return $image;
}
```

## Dynamic CSS Generation

Color functions in PHP let you generate CSS on the fly. This is useful for themed applications, white-label products, or any system where colors come from a database.

```php
function generateThemeCss(string $primaryHex): string
{
    $primaryHsl = rgbToHsl(...array_values(hexToRgb($primaryHex)));

    $lightHsl = ['h' => $primaryHsl['h'], 's' => $primaryHsl['s'], 'l' => 90];
    $darkHsl  = ['h' => $primaryHsl['h'], 's' => $primaryHsl['s'], 'l' => 20];
    $compHsl  = complementary($primaryHsl);

    $primary = $primaryHex;
    $light   = rgbToHex(...hslToRgb($lightHsl['h'], $lightHsl['s'], $lightHsl['l']));
    $dark    = rgbToHex(...hslToRgb($darkHsl['h'], $darkHsl['s'], $darkHsl['l']));
    $complement = rgbToHex(...hslToRgb($compHsl['h'], $compHsl['s'], $compHsl['l']));

    return <<<CSS
:root {
    --color-primary: {$primary};
    --color-primary-light: {$light};
    --color-primary-dark: {$dark};
    --color-complement: {$complement};
}
CSS;
}
```

## Data Visualization

Color mapping is essential for charts and heatmaps. A common task is mapping a value to a color on a gradient:

```php
function valueToColor(float $value, float $min, float $max): string
{
    $ratio = ($value - $min) / ($max - $min);
    $ratio = max(0, min(1, $ratio));

    // Red (0, 100, 50) -> Yellow (60, 100, 50) -> Green (120, 100, 50)
    $hue = (int) round((1 - $ratio) * 120);

    return rgbToHex(...hslToRgb($hue, 100, 50));
}
```

This produces a red-yellow-green heatmap automatically. Adjust the hue range for different color schemes.

## Color Accessibility

The Web Content Accessibility Guidelines (WCAG) define contrast ratios. Computing them in PHP lets you validate color choices programmatically:

```php
function relativeLuminance(int $r, int $g, int $b): float
{
    $linearize = function ($channel) {
        $channel /= 255;
        return $channel <= 0.03928
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $linearize($r)
         + 0.7152 * $linearize($g)
         + 0.0722 * $linearize($b);
}

function contrastRatio(string $hex1, string $hex2): float
{
    $rgb1 = hexToRgb($hex1);
    $rgb2 = hexToRgb($hex2);

    $l1 = relativeLuminance($rgb1['r'], $rgb1['g'], $rgb1['b']);
    $l2 = relativeLuminance($rgb2['r'], $rgb2['g'], $rgb2['b']);

    $lighter = max($l1, $l2);
    $darker  = min($l1, $l2);

    return ($lighter + 0.05) / ($darker + 0.05);
}

// WCAG AA requires 4.5:1 for normal text
contrastRatio('#FFFFFF', '#333333'); // ~13.5:1
```

## Summary

| Task | Approach |
|---|---|
| Store a color | Hex string for CSS, RGB array for math, HSL for manipulation |
| Convert between formats | Use the conversion functions above |
| Mix colors | Additive for screens, subtractive for print |
| Generate palettes | HSL-based harmony functions |
| Create images | GD library with `imagecolorallocate` |
| Validate accessibility | WCAG contrast ratio calculation |

Color math in PHP isn't complicated once you have the right functions in your toolbox. The key insight is that HSL makes color relationships intuitive, while RGB and hex are better for storage and output. Convert between them as needed, and you can handle any color task that comes your way.
