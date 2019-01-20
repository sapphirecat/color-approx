<?php

# we will probably never have 88-color support. There are a zillion 256-color
# palette listings on the internet, but I can find exactly zero for 88-color.
#
# although amazon has me covered if i wanted an 88-color eyeshadow kit

/**
 * @var array[] $p256 Palette for 256-color terminals. Indexed by index,
 * values are the corresponding HSL color array.
 */
$p256 = [];


/** Convert RGB to HSL. */
function rgb2hsl(string $hex): array {
    $nhex = strtolower(ltrim($hex, '#'));
    if (! preg_match('/^[[:xdigit:]]{6}$/', $nhex)) {
        return []; # return a false-value for convenience
    }

    $normalize = function(string $byte): float {
        return hexdec($byte) / 255;
    };
    [$r, $g, $b] = array_map($normalize, str_split($nhex, 2));
    if ($r == $g && $g == $b) {
        return [null, 0, $r]; # grays: 0 saturation, no hue
    }

    # find lightness and saturation
    $min = min($r, $g, $b);
    $max = max($r, $g, $b);
    $C = $max - $min;
    $l = ($min + $max) / 2;
    $s = $C / (1 - abs(2*$l - 1));

    # find hue (range 0..6)
    if ($max === $r) {
        $h6 = (($g - $b)/$C);
        # lazy floating-point modulus
        while ($h6 >= 6.0) {
            $h6 -= 6.0;
        }
    } elseif ($max === $g) {
        $h6 = (($b - $r)/$C) + 2;
    } else {
        $h6 = (($r - $g)/$C) + 4;
    }

    # multiply out hue to 0..360 and return everything
    return [$h6*60, $s, $l];
}

/** Find the nearest color to $hex in $palette. */
function nearest(array $target, array $palette) {
    $rv = null;
    $min = null;

    foreach ($palette as $color => $hsl) {
        # try to match to nearest lightness, then hue, then saturation.  we
        # use larger coefficients on more important channels.
        $error = 6*abs($hsl[2]-$target[2]) # lightness
            + 2*abs($hsl[1]-$target[1]);   # saturation

        # if both hues are present, add hue-based error
        if ($target[0] !== null && $hsl[0] !== null) {
            # we need to take the shortest path, since 359 and 0 are actually
            # just 1 degree apart. so if the difference is more than half the
            # circle, move one "back" a full circle. e.g. 30,300 will compare
            # as -60,30, i.e. 90 degrees between them.
            $hi = max($hsl[0], $target[0]);
            $lo = min($hsl[0], $target[0]);
            if ($hi - $lo > 180) {
                $hi -= 360;
            }
            $error += 6*abs($hi - $lo) / 360;
        } elseif ($target[0] !== $hsl[0]) {
            # exactly one hue is present, add additional saturation error
            $error += 3*max($target[1], $hsl[1]);
        }

        # if this is a new minimum error, save it (and the color)
        if ($min === null || $min > $error) {
            $rv = $color;
            $min = $error;
        }
    }

    return $rv;
}

function approx($hex) {
    global $p256;

    $target = rgb2hsl($hex);
    if (! $target) {
        error_log("$hex is invalid");
        return;
    }

    echo $hex, ' => ', nearest($target, $p256), PHP_EOL;
}

function init_palette_256() {
    global $p256;

    # set up the 6x6x6 RGB color cube starting at index 16
    $base = 16;
    $seq = ['00', '5f', '87', 'af', 'd7', 'ff'];
    $wrap = count($seq);
    $r = $g = $b = 0;
    while ($base < 232) {
        # store current color to the palette
        $hex = $seq[$r] . $seq[$g] . $seq[$b];
        $index = sprintf("%3d/%s", $base, $hex);
        $p256[$index] = rgb2hsl($hex);

        # bump our position in the color cube
        ++$b;
        if ($b >= $wrap) {
            $b = 0;
            ++$g;
            if ($g >= $wrap) {
                $g = 0;
                ++$r;
            }
        }

        # bump our index in the palette
        ++$base;
    }

    # set up the grayscale ramp
    $seed = 8;
    while ($base < 256) {
        $hex = str_repeat(dechex($seed), 3);
        $index = sprintf("%3d/%s", $base, $hex);
        $p256[$index] = [null, 0, $seed/255];

        # bump our color and index
        $seed += 10;
        ++$base;
    }
}


init_palette_256();
while (true) {
    echo 'hex code(s): ';
    flush();
    $line = fgets(STDIN);
    if (! ($line && trim($line))) {
        break;
    }

    foreach (preg_split('/\\s+/', $line, -1, PREG_SPLIT_NO_EMPTY) as $word) {
        approx($word);
    }
}
echo PHP_EOL;
