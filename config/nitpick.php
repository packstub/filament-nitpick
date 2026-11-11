<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default theme
    |--------------------------------------------------------------------------
    |
    | The theme a panel uses when its plugin does not call ->default(). A theme
    | id ("aurora") or class name. Overridable per panel with ->default().
    |
    */

    'default' => 'aurora',

    /*
    |--------------------------------------------------------------------------
    | Where a user's choice is kept
    |--------------------------------------------------------------------------
    |
    | "database" keeps one row per user and panel in the table below (run the
    | migration). "session" needs no migration but forgets the choice at logout.
    |
    */

    'store' => 'database',

    'table' => 'nitpick_theme_preferences',

    /*
    | What a panel's managers set for everyone, in the panel or a tenant
    | (->picker(manage: ...)): a theme, an accent and a lock. Run the
    | migration when you turn that on.
    */

    'panel_table' => 'nitpick_panel_themes',

    /*
    | The connection the preferences table lives on. Null follows the user
    | model's connection, which is the central one under multi-database tenancy.
    */

    'connection' => null,

    /*
    | The user model the preferences belong to. Null uses the default auth
    | provider's model.
    */

    'user_model' => null,

    /*
    | The type of your users' keys, for the preferences table's user_id
    | column: "int", "uuid" or "ulid". Null follows the app's morph key type
    | (Schema::morphUsingUuids(), morphUsingUlids()), else int. Set it before
    | running the migration.
    */

    'user_key_type' => null,

    /*
    |--------------------------------------------------------------------------
    | Colour overrides
    |--------------------------------------------------------------------------
    |
    | Colour slots applied on top of every theme's palette, in the shape of
    | Filament's ->colors([...]): ['primary' => Color::Indigo]. The plugin's
    | ->colors() call is the per-panel equivalent and wins over this.
    |
    */

    'colors' => [],

    /*
    | A stylesheet loaded after the active theme's, for per-project tweaks.
    | A path relative to public/ or an absolute URL.
    */

    'custom_css' => null,

    /*
    | Force a light/dark/system default regardless of the active theme's
    | preferred mode: "light", "dark", "system" or null.
    */

    'force_mode' => null,

    /*
    | The animation when someone picks a theme: "fade", "slide", "sweep" or
    | null for none; ->picker(transition: ...) sets it per panel. Skipped
    | for visitors who prefer reduced motion.
    */

    'transition' => 'fade',

];
