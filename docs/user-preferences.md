# User preferences

```php
NitpickPlugin::make()->userSelectable()
```

With user selection on, each signed-in user can choose the theme they see in that panel. The choice is theirs alone, saved for that panel, and applied on every device they sign in from (with the database store).

## The Appearance page

The plugin registers an **Appearance** page at `/{panel}/appearance` and links it from the user menu. It shows one card per offered theme with a swatch strip and the label; the description is the card's tooltip. Clicking a card previews the theme immediately and saves the choice. A theme that changes the panel's layout reloads the page instead.

![The Appearance page: a card per theme with its swatches, label and description, the current one marked](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/appearance.png)

![The user menu with the Appearance link under the light and dark toggle](https://raw.githubusercontent.com/packstub/art/main/filament-nitpick/docs/user-menu.png)

Hide the menu item and link the page from somewhere else:

```php
NitpickPlugin::make()->userSelectable(userMenuItem: false)
```

Replace the page with your own subclass:

```php
use Packstub\Nitpick\Filament\Pages\Appearance;

class ChooseYourLook extends Appearance
{
    protected static ?string $slug = 'look';
}

NitpickPlugin::make()->userSelectable()->appearancePage(ChooseYourLook::class)
```

Configure the picker on the page the way you would your own `ThemePicker` field (see [below](#cards-descriptions-and-swatches)):

```php
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;

NitpickPlugin::make()
    ->userSelectable()
    ->picker(fn (ThemePicker $picker) => $picker->descriptions())
```

Access follows the panel's own rules plus one more: the page returns 403 when user selection is off, so a `->userSelectable(fn () => ...)` closure can gate it per user.

## The `ThemePicker` field

The same picker is a form field. Drop it into your own profile page, a settings page or any Filament form:

```php
use Packstub\Nitpick\Filament\Forms\Components\ThemePicker;

ThemePicker::make(),
```

It fills itself with the user's current theme for the current panel and, by default, saves on change. It saves quietly: the panel changing its look is the confirmation, and someone trying a few themes is not left with a stack of notifications. Add `->notify()` for a "Theme updated" notification after each save.

To handle the value yourself on submit:

```php
ThemePicker::make('theme')
    ->persist(false),
```

```php
NitpickPlugin::get()->setUserTheme($data['theme']);
```

The field is `dehydrated(false)`, so it never lands in your model's data by accident.

### Cards: descriptions and swatches

Each card shows up to three swatches and the theme's name. Short cards scan faster, so the description is left off and shown as the card's tooltip. Show it under the name:

```php
ThemePicker::make()->descriptions()
```

The swatches come from the theme's `primary`, `info` and `success` colours, or from `->swatches([...])` on the theme. A theme whose colours are unknown (a plugin that sets them in `boot()`, say) shows three empty swatches, so the cards line up. Leave the strip out:

```php
ThemePicker::make()->swatches(false)
```

Both take a closure, evaluated when the form renders.

## Reading and writing a choice from code

```php
use Packstub\Nitpick\NitpickPlugin;

$plugin = NitpickPlugin::get();        // the current panel's plugin

$plugin->getActiveTheme();            // what this request renders
$plugin->getUserTheme();              // the signed-in user's choice, or null
$plugin->getUserTheme($someone);      // another user's choice for this panel
$plugin->setUserTheme('midnight');    // save for the signed-in user
$plugin->setUserTheme(null);          // clear, so the panel default applies again
$plugin->setUserTheme('aurora', $someone);
```

`setUserTheme()` refuses a theme the panel does not offer and fires `Packstub\Nitpick\Events\ThemeChanged` with the user, the panel id and the theme (null when cleared).

A stored choice that is no longer offered, because a theme was removed from the panel or uninstalled, is ignored and the default applies. Nothing breaks for the user.

## Where the choice is kept

| `store` | What happens |
| --- | --- |
| `database` (default) | One row per user and panel in `theme_preferences`. Survives logout and follows the user across devices. Needs the migration. |
| `session` | Kept in the session. No migration, forgotten at logout. Handy for a demo or a first try. |

Bring your own store by binding `Packstub\Nitpick\Contracts\ThemePreferenceStore` in a service provider; it is two methods, `get()` and `set()`.

## Live preview

When user selection is on, the page head carries every offered palette as CSS variables scoped to `[data-packstub-theme="…"]` and a tiny script exposing `window.PackstubThemes`:

```js
window.PackstubThemes.current()          // 'aurora'
window.PackstubThemes.list()             // ['aurora', 'midnight']
window.PackstubThemes.preview('midnight') // swap stylesheet, palette and mode now
```

`preview()` only changes the current page; the picker follows it with the save. A `packstub-theme-changed` event fires on `document` after each preview. A theme that [configures the panel](creating-a-theme.md#configuring-the-panel), or a move away from one, shows only after a page load: `preview()` then adopts the theme's light/dark mode and returns `false`, and the picker reloads the page once the choice is saved.

Continue with [Creating a theme](creating-a-theme.md).
