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

        // One statement on the unique key: two saves at the same moment never make two rows.
        $this->table()->upsert(
            [[...$this->keys($panelId, $tenant), 'theme_id' => $theme->themeId, 'accent' => $theme->accent, 'locked' => $theme->locked, 'created_at' => now(), 'updated_at' => now()]],
            ['panel_id', 'tenant_type', 'tenant_id'],
            ['theme_id', 'accent', 'locked', 'updated_at'],
        );
    }

    /**
     * @return Builder
     */
    protected function query(string $panelId, ?Model $tenant)
    {
        return $this->table()->where($this->keys($panelId, $tenant));
    }

    /**
     * The tenant's key as a string, so the column holds integer, UUID and
     * ULID keys alike, compared as text on every database. The whole panel
     * is '' rather than null, so the unique key holds for it too (a unique
     * index never treats two nulls as equal).
     *
     * @return array{panel_id: string, tenant_type: string, tenant_id: string}
     */
    protected function keys(string $panelId, ?Model $tenant): array
    {
        return [
            'panel_id' => $panelId,
            'tenant_type' => $tenant?->getMorphClass() ?? '',
            'tenant_id' => $tenant === null ? '' : (string) $tenant->getKey(),
        ];
    }

    protected function table(): Builder
    {
        return DB::connection(ThemeManager::connectionName())->table(ThemeManager::panelTableName());
    }
}
