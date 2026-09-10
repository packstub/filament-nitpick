<?php

namespace Packstub\Themes\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Where a user's theme choice for a panel is kept.
 */
interface ThemePreferenceStore
{
    public function get(Authenticatable $user, string $panelId): ?string;

    /**
     * Null forgets the preference so the panel default applies again.
     */
    public function set(Authenticatable $user, string $panelId, ?string $themeId): void;
}
