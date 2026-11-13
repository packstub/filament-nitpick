<?php

namespace Packstub\Nitpick\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A ThemePreferenceStore that also keeps a user's accent colour per panel.
 * Both bundled stores do; a store of your own without it simply offers no
 * accent row in the picker.
 */
interface StoresAccents
{
    /**
     * A Filament colour name ("indigo") or a hex colour ("#4f46e5").
     */
    public function getAccent(Authenticatable $user, string $panelId): ?string;

    /**
     * Null forgets the accent so the theme's own colour applies again.
     */
    public function setAccent(Authenticatable $user, string $panelId, ?string $accent): void;
}
