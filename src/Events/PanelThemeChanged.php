<?php

namespace Packstub\Nitpick\Events;

use Illuminate\Database\Eloquent\Model;
use Packstub\Nitpick\PanelTheme;

/**
 * Fired after a manager changes what everyone in a panel, or in a tenant
 * (null: the whole panel), sees: the theme, the accent or the lock.
 */
class PanelThemeChanged
{
    public function __construct(
        public readonly string $panelId,
        public readonly ?Model $tenant,
        public readonly PanelTheme $theme,
    ) {}
}
