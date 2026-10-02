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

    'table' => 'theme_preferences',

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
    |--------------------------------------------------------------------------
    | Theme transition
    |--------------------------------------------------------------------------
    |
    | How a theme picked in the picker takes over the screen: "reveal" (it
    | opens out from the picked card), "fade" (a short cross-fade) or false
    | (at once). Skipped for visitors who prefer reduced motion.
    */

    'transition' => 'reveal',

];
