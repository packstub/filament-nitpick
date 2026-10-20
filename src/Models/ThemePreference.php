<?php

namespace Packstub\Nitpick\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Packstub\Nitpick\ThemeManager;

/**
 * @property int $id
 * @property string $user_type
 * @property int | string $user_id
 * @property string $panel_id
 * @property string $theme_id
 */
class ThemePreference extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return ThemeManager::tableName();
    }

    public function getConnectionName(): ?string
    {
        return ThemeManager::connectionName();
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
