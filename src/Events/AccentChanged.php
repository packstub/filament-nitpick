<?php

namespace Packstub\Nitpick\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Fired after a user's accent colour for a panel is saved. A null accent
 * means it was cleared and the theme's own colour applies again.
 */
class AccentChanged
{
    public function __construct(
        public readonly Authenticatable $user,
        public readonly string $panelId,
        public readonly ?string $accent,
    ) {}
}
