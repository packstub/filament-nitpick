<?php

namespace Packstub\Nitpick\Stores;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Packstub\Nitpick\Contracts\ThemePreferenceStore;
use Packstub\Nitpick\Models\ThemePreference;

class DatabaseThemePreferenceStore implements ThemePreferenceStore
{
    public function get(Authenticatable $user, string $panelId): ?string
    {
        return $this->query($user, $panelId)->value('theme_id');
    }

    public function set(Authenticatable $user, string $panelId, ?string $themeId): void
    {
        if ($themeId === null) {
            $this->query($user, $panelId)->delete();

            return;
        }

        ThemePreference::query()->updateOrCreate(
            $this->keys($user, $panelId),
            ['theme_id' => $themeId],
        );
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
