# color-approx

Find xterm-256color approximations of RRGGBB triples

# Description

This is a minimally-engineered PHP script for finding a "close" color from the
xterm-256color palette, given a hex triple like `#a0df00`.

I used it to build the first pass at the 256-color mode for
[zora-theme-vim](https://github.com/sapphirecat/zora-theme-vim/).  There were
a couple of manual tweaks required, but overall, it worked wonders.

# Internals

The "closest" color is found by converting to HSL, then finding the color in
the palette with the lowest total error value.  Error values are tuned based
on gut feeling and hand-weighting, to push the result to a close _lightness,_
allowing less accuracy in hue and even less in saturation.

Then, there's an extra error factored in when comparing a palette gray to a
non-gray target color.  This prevents the lenience in saturation from turning
_everything_ to gray.

# Other comments

I wanted to try L\*C\*h, but to use the formulae I found, I'd have to convert
RGB to XYZ to L\*a\*b\* to L\*C\*h to get there.

This is a weekend hack.  I went with HSL.

# License

MIT.
