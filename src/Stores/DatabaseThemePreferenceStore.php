<?php

namespace Packstub\Nitpick\Stores;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Packstub\Nitpick\Contracts\StoresAccents;
use Packstub\Nitpick\Contracts\ThemePreferenceStore;
use Packstub\Nitpick\Models\ThemePreference;

class DatabaseThemePreferenceStore implements StoresAccents, ThemePreferenceStore
{
    public function get(Authenticatable $user, string $panelId): ?string
    {
        return $this->query($user, $panelId)->value('theme_id');
    }

    public function set(Authenticatable $user, string $panelId, ?string $themeId): void
    {
        $this->put($user, $panelId, ['theme_id' => $themeId]);
    }

    public function getAccent(Authenticatable $user, string $panelId): ?string
    {
        return $this->query($user, $panelId)->value('accent');
    }

    public function setAccent(Authenticatable $user, string $panelId, ?string $accent): void
    {
        $this->put($user, $panelId, ['accent' => $accent]);
    }

    /**
     * Saves the values on the user's row for the panel, and removes the row
     * once it holds neither a theme nor an accent.
     *
     * @param  array<string, string | null>  $values
     */
    protected function put(Authenticatable $user, string $panelId, array $values): void
    {
        $preference = ThemePreference::query()->firstOrNew($this->keys($user, $panelId));
        $preference->fill($values);

        if ($preference->theme_id === null && $preference->accent === null) {
            if ($preference->exists) {
                $preference->delete();
            }

            return;
        }

        $preference->save();
    }

    /**
     * @return Builder<ThemePreference>
     */
    protected function query(Authenticatable $user, string $panelId)
    {
        return ThemePreference::query()->where($this->keys($user, $panelId));
    }

    /**
     * @return array{user_type: string, user_id: mixed, panel_id: string}
     */
    protected function keys(Authenticatable $user, string $panelId): array
    {
        return [
            'user_type' => $user instanceof Model ? $user->getMorphClass() : $user::class,
            'user_id' => $user->getAuthIdentifier(),
            'panel_id' => $panelId,
        ];
    }
}
