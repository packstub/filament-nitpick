<?php

namespace Packstub\Nitpick;

/**
 * What a panel's managers set for everyone in the panel, or in one tenant:
 * a theme, an accent colour, and whether the picker is locked to them.
 */
final class PanelTheme
{
    public function __construct(
        public readonly ?string $themeId = null,
        public readonly ?string $accent = null,
        public readonly bool $locked = false,
    ) {}

    public function with(?string $themeId = null, ?string $accent = null, ?bool $locked = null, bool $clearTheme = false, bool $clearAccent = false): self
    {
        return new self(
            $clearTheme ? null : ($themeId ?? $this->themeId),
            $clearAccent ? null : ($accent ?? $this->accent),
            $locked ?? $this->locked,
        );
    }

    /**
     * A tenant's setting over the panel's: each field the tenant leaves unset
     * comes from the panel's, and a lock on either holds.
     */
    public static function over(?self $tenant, ?self $panel): ?self
    {
        if ($tenant === null || $panel === null) {
            return $tenant ?? $panel;
        }

        return new self($tenant->themeId ?? $panel->themeId, $tenant->accent ?? $panel->accent, $tenant->locked || $panel->locked);
    }

    public function isEmpty(): bool
    {
        return $this->themeId === null && $this->accent === null && ! $this->locked;
    }
}
