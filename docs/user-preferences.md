# User preferences

```php
ThemesPlugin::make()->userSelectable()
```

With user selection on, each signed-in user can choose the theme they see in that panel. The choice is theirs alone, saved for that panel, and applied on every device they sign in from (with the database store).

## The Appearance page

The plugin registers an **Appearance** page at `/{panel}/appearance` and links it from the user menu. It shows one card per offered theme with a swatch strip, the label and the description. Clicking a card previews the theme immediately, saves the choice, and shows a confirmation.

Hide the menu item and link the page from somewhere else:

```php
ThemesPlugin::make()->userSelectable(userMenuItem: false)
```

Replace the page with your own subclass:

```php
use Packstub\Themes\Filament\Pages\Appearance;

class ChooseYourLook extends Appearance
{
    protected static ?string $slug = 'look';
}

ThemesPlugin::make()->userSelectable()->appearancePage(ChooseYourLook::class)
```

Access follows the panel's own rules plus one more: the page returns 403 when user selection is off, so a `->userSelectable(fn () => ...)` closure can gate it per user.

## The `ThemePicker` field

The same picker is a form field. Drop it into your own profile page, a settings page or any Filament form:

```php
use Packstub\Themes\Filament\Forms\Components\ThemePicker;

ThemePicker::make(),
```

It fills itself with the user's current theme for the current panel and, by default, saves on change and notifies. To handle the value yourself on submit:

```php
ThemePicker::make('theme')
    ->persist(false)
    ->notify(false),
```

```php
ThemesPlugin::get()->setUserTheme($data['theme']);
```

The field is `dehydrated(false)`, so it never lands in your model's data by accident.

## Reading and writing a choice from code

```php
use Packstub\Themes\ThemesPlugin;

$plugin = ThemesPlugin::get();        // the current panel's plugin

$plugin->getActiveTheme();            // what this request renders
$plugin->getUserTheme();              // the signed-in user's choice, or null
$plugin->getUserTheme($someone);      // another user's choice for this panel
$plugin->setUserTheme('midnight');    // save for the signed-in user
$plugin->setUserTheme(null);          // clear, so the panel default applies again
$plugin->setUserTheme('aurora', $someone);
```

`setUserTheme()` refuses a theme the panel does not offer and fires `Packstub\Themes\Events\ThemeChanged` with the user, the panel id and the theme (null when cleared).

A stored choice that is no longer offered, because a theme was removed from the panel or uninstalled, is ignored and the default applies. Nothing breaks for the user.

## Where the choice is kept

| `store` | What happens |
| --- | --- |
| `database` (default) | One row per user and panel in `theme_preferences`. Survives logout and follows the user across devices. Needs the migration. |
| `session` | Kept in the session. No migration, forgotten at logout. Handy for a demo or a first try. |

Bring your own store by binding `Packstub\Themes\Contracts\ThemePreferenceStore` in a service provider; it is two methods, `get()` and `set()`.

## Live preview

When user selection is on, the page head carries every offered palette as CSS variables scoped to `html[data-packstub-theme="…"]` and a tiny script exposing `window.PackstubThemes`:

```js
window.PackstubThemes.current()          // 'aurora'
window.PackstubThemes.list()             // ['aurora', 'midnight']
window.PackstubThemes.preview('midnight') // swap stylesheet, palette and mode now
```

`preview()` only changes the current page; the picker follows it with the save. A `packstub-theme-changed` event fires on `document` after each preview.

Continue with [Creating a theme](creating-a-theme.md).
