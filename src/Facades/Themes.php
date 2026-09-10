<?php

namespace Packstub\Themes\Facades;

use Illuminate\Support\Facades\Facade;
use Packstub\Themes\ThemeManager;

/**
 * @method static ThemeManager register(string|\Packstub\Themes\Contracts\Theme ...$themes)
 * @method static array<string, \Packstub\Themes\Contracts\Theme> all()
 * @method static \Packstub\Themes\Contracts\Theme|null find(string|\Packstub\Themes\Contracts\Theme $theme)
 * @method static \Packstub\Themes\Contracts\Theme resolve(string|\Packstub\Themes\Contracts\Theme $theme)
 * @method static bool has(string|\Packstub\Themes\Contracts\Theme $theme)
 * @method static string assetId(\Packstub\Themes\Contracts\Theme $theme)
 * @method static \Packstub\Themes\Contracts\ThemePreferenceStore store()
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
