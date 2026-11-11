<?php

namespace Packstub\Nitpick\Stores;

use Illuminate\Contracts\Auth\Authenticatable;
use Packstub\Nitpick\Contracts\StoresAccents;
use Packstub\Nitpick\Contracts\ThemePreferenceStore;

class SessionThemePreferenceStore implements StoresAccents, ThemePreferenceStore
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

    public function getAccent(Authenticatable $user, string $panelId): ?string
    {
        return session()->get($this->accentKey($user, $panelId));
    }

    public function setAccent(Authenticatable $user, string $panelId, ?string $accent): void
    {
        $accent === null
            ? session()->forget($this->accentKey($user, $panelId))
            : session()->put($this->accentKey($user, $panelId), $accent);
    }

    protected function accentKey(Authenticatable $user, string $panelId): string
    {
        return "nitpick-accent.{$panelId}.{$user->getAuthIdentifier()}";
    }

    protected function key(Authenticatable $user, string $panelId): string
    {
        return "nitpick.{$panelId}.{$user->getAuthIdentifier()}";
    }
}
