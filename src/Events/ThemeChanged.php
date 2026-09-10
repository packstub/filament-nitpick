<?php

namespace Packstub\Themes\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Packstub\Themes\Contracts\Theme;

/**
 * Fired after a user's theme preference for a panel is saved. A null theme
 * means the preference was cleared and the panel default applies again.
 */
class ThemeChanged
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $panelId,
        public readonly ?Theme $theme,
    ) {}
}
