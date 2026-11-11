<?php

namespace Packstub\Nitpick\Stores;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Packstub\Nitpick\Contracts\PanelThemeStore;
use Packstub\Nitpick\PanelTheme;
use Packstub\Nitpick\ThemeManager;

class DatabasePanelThemeStore implements PanelThemeStore
{
    public function get(string $panelId, ?Model $tenant): ?PanelTheme
    {
        $row = $this->query($panelId, $tenant)->first();

        return $row === null ? null : new PanelTheme($row->theme_id, $row->accent, (bool) $row->locked);
    }

    public function set(string $panelId, ?Model $tenant, PanelTheme $theme): void
    {
        if ($theme->isEmpty()) {
            $this->query($panelId, $tenant)->delete();

            return;
        }

        $values = ['theme_id' => $theme->themeId, 'accent' => $theme->accent, 'locked' => $theme->locked, 'updated_at' => now()];

        if ($this->query($panelId, $tenant)->exists()) {
            $this->query($panelId, $tenant)->update($values);

            return;
        }

        $this->table()->insert([...$this->keys($panelId, $tenant), ...$values, 'created_at' => now()]);
    }

    /**
     * @return Builder
     */
    protected function query(string $panelId, ?Model $tenant)
    {
        $query = $this->table()->where('panel_id', $panelId);

        foreach ($this->keys($panelId, $tenant) as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        return $query;
    }

    /**
     * The tenant's key as a string, so the column holds integer, UUID and
     * ULID keys alike, compared as text on every database.
     *
     * @return array{panel_id: string, tenant_type: string | null, tenant_id: string | null}
     */
    protected function keys(string $panelId, ?Model $tenant): array
    {
        return [
            'panel_id' => $panelId,
            'tenant_type' => $tenant?->getMorphClass(),
            'tenant_id' => $tenant === null ? null : (string) $tenant->getKey(),
        ];
    }

    protected function table(): Builder
    {
        return DB::connection(ThemeManager::connectionName())->table(ThemeManager::panelTableName());
    }
}
