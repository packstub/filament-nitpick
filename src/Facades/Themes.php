<?php

namespace Packstub\Nitpick\Facades;

use Illuminate\Support\Facades\Facade;
use Packstub\Nitpick\ThemeManager;

/**
 * @method static ThemeManager register(string|\Packstub\Nitpick\Contracts\Theme ...$themes)
 * @method static array<string, \Packstub\Nitpick\Contracts\Theme> all()
 * @method static \Packstub\Nitpick\Contracts\Theme|null find(string|\Packstub\Nitpick\Contracts\Theme $theme)
 * @method static \Packstub\Nitpick\Contracts\Theme resolve(string|\Packstub\Nitpick\Contracts\Theme $theme)
 * @method static bool has(string|\Packstub\Nitpick\Contracts\Theme $theme)
 * @method static string assetId(\Packstub\Nitpick\Contracts\Theme $theme)
 * @method static \Packstub\Nitpick\Contracts\ThemePreferenceStore store()
 *
 * @see ThemeManager
 */
class Themes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ThemeManager::class;
    }
}
