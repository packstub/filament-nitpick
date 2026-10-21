<?php

namespace Packstub\Nitpick\Enums;

/**
 * How the panel changes when someone picks a theme. Each runs in two steps:
 * a cover, black or white for the light/dark mode the new theme uses, comes
 * over the panel, the theme changes underneath (live, or by a page reload),
 * and the cover lifts to show it.
 */
enum Transition: string
{
    /** The cover fades in, then out. */
    case Fade = 'fade';

    /** The cover slides in from the left and out to the right, like a curtain. */
    case Slide = 'slide';

    /** The cover sweeps in behind an edge turning about a point below the panel's right corner, then sweeps on past. */
    case Sweep = 'sweep';
}
