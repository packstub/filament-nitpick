<?php

namespace Packstub\Nitpick\Contracts;

use Illuminate\Database\Eloquent\Model;
use Packstub\Nitpick\PanelTheme;

/**
 * Where a panel's managers keep the theme they set for everyone: per panel,
 * and per tenant on a tenant panel (a null tenant is the whole panel).
 */
interface PanelThemeStore
{
    public function get(string $panelId, ?Model $tenant): ?PanelTheme;

    /**
     * An empty PanelTheme forgets the setting.
     */
    public function set(string $panelId, ?Model $tenant, PanelTheme $theme): void;
}
