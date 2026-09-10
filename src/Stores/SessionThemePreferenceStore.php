<?php

namespace Packstub\Themes\Stores;

use Illuminate\Contracts\Auth\Authenticatable;
use Packstub\Themes\Contracts\ThemePreferenceStore;

class SessionThemePreferenceStore implements ThemePreferenceStore
{
    public function get(Authenticatable $user, string $panelId): ?string
    {
        return session()->get($this->key($user, $panelId));
    }

    public function set(Authenticatable $user, string $panelId, ?string $themeId): void
    {
        if ($themeId === null) {
            session()->forget($this->key($user, $panelId));

            return;
        }

        session()->put($this->key($user, $panelId), $themeId);
    }

    protected function key(Authenticatable $user, string $panelId): string
    {
        return "packstub-themes.{$panelId}.{$user->getAuthIdentifier()}";
    }
}
