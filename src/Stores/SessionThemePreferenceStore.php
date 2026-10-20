<?php

namespace Packstub\Nitpick\Stores;

use Illuminate\Contracts\Auth\Authenticatable;
use Packstub\Nitpick\Contracts\ThemePreferenceStore;

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
        return "nitpick.{$panelId}.{$user->getAuthIdentifier()}";
    }
}
