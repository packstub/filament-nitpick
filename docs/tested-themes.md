# Tested themes

The free Filament 5 themes that have been run through Nitpick's picker, in a Laravel 13.38 app with Filament 5.11.0: each one is installed the way its own docs describe and registered with `Theme::make()`, then picked from Aurora, screenshotted in light and dark, and left again, with a check that none of its stylesheets, scripts, colours, fonts or classes stay behind on the next theme. The app and its scripts are public at [packstub/nitpick-theme-compat](https://github.com/packstub/nitpick-theme-compat), and the run is repeated before each Nitpick release.

The column on the right is what the theme takes beyond `->plugin()`; [Themes](themes.md#themes-from-the-filament-plugin-directory) explains each kind of setup. Versions are the ones tested on 2026-10-27.

## Themes

| Theme | Package | Version | Setup with Nitpick |
| --- | --- | --- | --- |
| Aberdeen | `spykapps/theme-aberdeen` | 1.0.1 | `->plugin()` and a `vite:` file of its own (Filament's theme, then its CSS), instead of its install command |
| Abyss | `osamanagi/filament-abyss-theme` | 1.2.0 | `->plugin()`; its theme entry in `vite.config.js`, as its docs ask |
| Akin | `lara-zeus/akin` | 3.0.1 | `->plugin()` and a `vite:` file importing Filament's theme, then Akin's |
| Aurum | `thalysjuvenal/aurum-filament-theme` | 1.1.2 | `->plugin()` |
| BenRiadh | `benriadh1/filament-benriadh-theme` | dev-main | `->plugin()` after its install command, which publishes the stylesheet |
| Bodega Lite | `nodonhq/filament-bodega-lite` | 1.0.0 | `->plugin()` |
| Bonsai | `qalainau/bonsai-theme` | 0.1.0 | `->plugin()` |
| Brisk | `filafly/brisk` | 1.1.0 | `->plugin()` and a `vite:` file importing Filament's theme, then `filafly/brisk/resources/css/theme.css` |
| Compact | `nehalpatel/filament-compact-theme` | 1.0.0 | `->stylesheet('vite:vendor/nehalpatel/filament-compact-theme/resources/css/theme.css')`; no plugin class |
| DaisyUI Themes | `osamanagi/filament-daisy-ui-themes` | 1.1.0 | one `Theme::make()` per DaisyUI theme: `->plugin(fn () => FilamentDaisyUiThemesPlugin::make()->themes(['dracula'])->defaultTheme('dracula')->themeSwitcher(false))` |
| Edinburgh | `spykapps/theme-edinburgh` | 1.0.3 | `->plugin()` and a `vite:` file of its own, instead of its install command |
| Editorial | `jeffersongoncalves/filament-editorial-theme` | 3.5.0 | `->plugin()` and a `vite:` file |
| Filament Tribute | `laboiteacode/filament-tribute-theme` | 1.0.0 | `->plugin()` and a `vite:` file (Filament's theme, its CSS, `@source` for its views) |
| Inverness | `spykapps/theme-inverness` | 1.0.0 | `->plugin()` and a `vite:` file of its own, instead of its install command |
| Italia | `robyconte/filament-italia` | 1.5.0 | `->panel(fn (Panel $panel) => FilamentItaliaTheme::applyTo($panel))`, its published theme file in Vite; light mode by design |
| Kinetics | `otatechie/filament-kinetics` | 1.7.0 | `->plugin(fn () => KineticsPlugin::make()->appearancePage(false))` and `->panel(fn (Panel $panel) => $panel->viteTheme('resources/css/themes/kinetics.css'))`, the file its docs describe; add `->colors()` for swatches |
| Laravel Tribute | `laboiteacode/filament-laravel-tribute-template` | 1.0.2 | `->plugin()` and a `vite:` file, as Filament Tribute |
| MeUI | `saivra/meui-theme` | 0.2.0 | `->plugin()` and its migration; `->label('MeUI')` |
| Mía | `johnrivera7/filament-mia-theme` | 0.2.3 | `->plugin()` |
| Moonlight | `notwonderful/filament-moonlight-theme` | 1.0.3 | `->plugin()`; add `->colors()` for swatches |
| Neobrutalism | `caresome/filament-neobrutalism-theme` | 2.1.0 | `->plugin()`; add `->colors()` for swatches |
| Nord | `andreia/filament-nord-theme` | 3.2.0 | `->plugin()` and a `vite:` file importing Filament's theme, then Nord's |
| Noreviq | `noreviq/filament-theme` | 1.0.7 | `->plugin()` |
| Polder | `vtech9nl/filament-polder-theme` | 0.3.2 | `->plugin()` and a `vite:` file (Filament's theme, its `layers.css`, its `theme.css`) |
| Press | `elemind/press-filament-theme` | 1.0.2 | `->plugin()` |
| Qt5 | `khwr/filament-qt5-theme` | 1.1.0 | `->plugin()` |
| Rail | `koassi/filament-rail` | 1.0.0 | `->plugin()` and a `vite:` file; its plugin turns on database notifications, so run `php artisan make:notifications-table` and migrate first |
| Shadcn (irajul) | `irajul/filament-shadcn-theme` | 1.0.0 | `->plugin()`; nothing to build |
| Shadcn (Openplain) | `openplain/filament-shadcn-theme` | 1.1.0 | `->colors(['primary' => ShadcnColor::Default])` and its rules as `->stylesheet()`, with the package under `dont-discover` |
| Shoplab | `shoplab/shoplab-filament-theme` | 1.2.1 | `->stylesheet()` with a `vite:` wrapper (Filament's theme, then `src/Resources/css/theme.css`); leave its plugin out until a release ships the `dist/` file it registers |
| Simple Theme | `tomatophp/filament-simple-theme` | 5.0.0 | `->plugin()`; moves the user menu to the sidebar |
| Sleek | `hansenmakangiras/filament-sleek-theme` | 1.3.1 | `->plugin(fn () => FilamentSleekTheme::make()->theme('resources/css/themes/sleek.css'))`, that file a wrapper importing Filament's theme, then Sleek's |
| Tailscale | `vlados/filament-tailscale-theme` | 1.0.2 | `->plugin()` and `->stylesheet()` with its built CSS, the package under `dont-discover`; light mode by design |

Two more work when picked, and are best as the only theme of a panel, because their service provider changes every panel in the app as soon as the package is installed: Vesper (`suraj-kashyap-dev/vesper` 2.0.0) replaces Filament's layout views everywhere, and Awrel (`khoirulaksara/awrel` 1.5.5) loads its CSS on every panel. Arasy (`rafyakbar/arasy-theme` 1.2.0) works through `->plugin()` and `->panel(fn (Panel $panel) => $panel->viteTheme(...)->sidebarCollapsibleOnDesktop())`: its layout makes room for the full sidebar when the sidebar is collapsible on desktop. Yavuz (`yavuz-dev/filament-theme` 1.2.3) works with `->picker(modal: false)`, since its topbar does not render the user menu's modals yet.

Not yet run: the paid themes, the brand themes of one organisation, Cyber and Panel Branding (waiting on a Laravel 13 release), and themes built for a CMS of their own.

## Colour switchers and branding tools beside Nitpick

Each one installed next to Nitpick on the same panel, set up as its docs say, with Midnight, Bodega Lite and Abyss picked and left under it. All of them coexist with the picker; where a tool and a theme both set the palette or the font, the tool's own default applies, so keep one colour switcher per panel.

| Tool | Package | Version | Note |
| --- | --- | --- | --- |
| Light Switch | `awcodes/light-switch` | 3.2.0 | the auth pages |
| Palette Switcher | `octopyid/filament-palette` | 1.1.0 | |
| Panel Theme Isolation | `jeffersongoncalves/filament-panel-theme-isolation` | 3.0.0 | reads the per-panel key, falls back to the one Nitpick writes |
| Theme Customizer | `cuongpham2107/filament-theme-customizer` | 1.1.0 | |
| Themes (Asign) | `asignua/filament-themes` | 1.0.0 | its own theme page beside Appearance: offer one picker or the other |
| Themes (silasrm) | `silasrm/filament-themes` | 1.0.1 | applies its own preset on every page; its presets and Nitpick's themes are two answers to the same question, so pick one |
| UI Switcher | `andreia/filament-ui-switcher` | 1.1.2 | its default font applies under every theme until the user changes it |
| White Label | `ashrafic/filament-white-label` | 1.0.8 | its default font applies under every theme until the user changes it |

Tried a theme that is not here? [Open an issue](https://github.com/packstub/filament-nitpick/issues) with the package name and what you saw, and it joins the next run.
