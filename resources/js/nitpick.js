// Nitpick: changes a panel's theme from the picker, with the panel's transition. The page's head carries
// a few inline lines that stamp the theme on <html> and start a page that a pick reloaded under the cover
// before it paints (NitpickPlugin::renderHead()); this file does the rest and exposes window.Nitpick.
(function () {
    var html = document.documentElement;
    // The offered themes, their stylesheets and the transition, from a JSON block in the head: null when
    // users may not switch. Read again after a navigation, which may land on another panel.
    var manifest = readManifest();
    document.addEventListener('livewire:navigated', function () { manifest = readManifest(); });

    function readManifest() {
        var element = document.getElementById('nitpick-manifest');
        try { return element ? JSON.parse(element.textContent) : null; } catch (e) { return null; }
    }
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var note = 'nitpick:cover';

    function stamp() {
        var meta = document.querySelector('meta[name="nitpick-theme"]');
        if (meta) { html.setAttribute('data-nitpick-theme', meta.getAttribute('content')); }
    }

    function isDark(mode) {
        if (! mode) { return html.classList.contains('dark'); }
        return mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    }

    function applyMode(mode) {
        if (! mode) { return; }
        // Filament 5.10.2 renamed its key from `theme` to `filament:theme:mode`: both are written.
        try { localStorage.setItem('filament:theme:mode', mode); localStorage.setItem('theme', mode); } catch (e) {}
        var dark = isDark(mode);
        html.classList.toggle('dark', dark);
        if (window.Alpine && typeof Alpine.store === 'function' && Alpine.store('theme') !== undefined) {
            Alpine.store('theme', dark ? 'dark' : 'light');
        }
        // The light/dark switcher in the user menu read the mode once, when the page loaded:
        // set its own state, as a click on it would, so it marks the mode now in use.
        if (window.Alpine && typeof Alpine['$data'] === 'function') {
            document.querySelectorAll('.fi-theme-switcher').forEach(function (switcher) {
                var data = Alpine['$data'](switcher);
                if (data && 'theme' in data) { data.theme = mode; }
            });
        }
    }

    // Whether changing to a theme takes a page load: it, or the active one, configures the panel.
    function needsReload(slug) {
        var active = manifest.themes[html.getAttribute('data-nitpick-theme')];
        return !! (manifest.themes[slug].reload || (active && active.reload));
    }

    // Points the head at a theme. Returns the stylesheet links it changed, or null for a
    // theme that configures the panel: it shows only after a page load (the picker reloads
    // once the pick is saved), so only its light/dark mode is taken now.
    function apply(slug) {
        var theme = manifest.themes[slug];
        if (needsReload(slug)) {
            applyMode(theme.mode);
            return null;
        }
        // The theme's stylesheets, in order: links already in the head are reused,
        // new ones follow the last (ahead of the custom stylesheet), spare ones go.
        var links = Array.prototype.slice.call(document.querySelectorAll('link[data-nitpick-theme-stylesheet]'));
        var custom = document.getElementById('nitpick-theme-custom');
        var last = null;
        var changed = [];
        theme.hrefs.forEach(function (href, index) {
            var link = links[index];
            if (! link) {
                link = document.createElement('link');
                link.rel = 'stylesheet';
                link.setAttribute('data-nitpick-theme-stylesheet', '');
                if (last) {
                    last.insertAdjacentElement('afterend', link);
                } else if (custom) {
                    custom.insertAdjacentElement('beforebegin', link);
                } else {
                    document.head.appendChild(link);
                }
            }
            if (index === 0) { link.id = 'nitpick-theme-active'; }
            if (link.getAttribute('href') !== href) {
                link.setAttribute('href', href);
                changed.push(link);
            }
            link.setAttribute('data-nitpick-theme', slug);
            last = link;
        });
        links.slice(theme.hrefs.length).forEach(function (link) { link.remove(); });
        html.setAttribute('data-nitpick-theme', slug);
        var meta = document.querySelector('meta[name="nitpick-theme"]');
        if (meta) { meta.setAttribute('content', slug); }
        applyMode(theme.mode);
        document.dispatchEvent(new CustomEvent('nitpick:theme-changed', { detail: { theme: slug } }));
        return changed;
    }

    // The transition's cover: a fixed layer over the page whose shape animates only its
    // transform or opacity, which the browser runs off the main thread, so the motion stays
    // smooth while the theme's stylesheets load and the page restyles under it. A page that
    // a pick reloaded starts under html::after (styled in #nitpick-cover) until the layer
    // takes over.
    var transitions = {
        fade: { in: 220, out: 320, keyframes: function (coming) { return coming ? [{ opacity: 0 }, { opacity: 1 }] : [{ opacity: 1 }, { opacity: 0 }]; } },
        slide: { in: 320, out: 420, keyframes: function (coming) { return coming ? [{ transform: 'translateX(-100%)' }, { transform: 'translateX(0)' }] : [{ transform: 'translateX(0)' }, { transform: 'translateX(100%)' }]; } },
        sweep: { in: 400, out: 520, shape: sweep, keyframes: function () { return sweepKeyframes(); } }
    };

    // The README sweep: a half-plane whose edge turns about a point below the panel's right
    // corner. Coming, it covers what lies below that edge, so the panel fills
    // from the left as the edge rises; going, what lies above it, so it empties the same way.
    // The half-plane is a rectangle reaching just past the panel's farthest corner from the
    // point, so the layer the browser moves stays as small as it can be.
    function sweepPivot() {
        return [window.innerWidth * 1.02, window.innerHeight * 1.55];
    }

    // The share of the panel the edge has passed at this angle: the panel's rectangle cut by
    // the edge, measured.
    function sweptShare(angle) {
        var w = window.innerWidth, h = window.innerHeight, p = sweepPivot(), r = angle * Math.PI / 180;
        var dx = -Math.cos(r), dy = -Math.sin(r), corners = [[0, 0], [w, 0], [w, h], [0, h]], cut = [], area = 0;
        var side = function (q) { return dx * (q[1] - p[1]) - dy * (q[0] - p[0]); };
        corners.forEach(function (a, i) {
            var b = corners[(i + 1) % 4], sa = side(a), sb = side(b);
            if (sa < 0) { cut.push(a); }
            if ((sa < 0) !== (sb < 0)) { var t = sa / (sa - sb); cut.push([a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t]); }
        });
        cut.forEach(function (a, i) { var b = cut[(i + 1) % cut.length]; area += a[0] * b[1] - b[0] * a[1]; });
        return Math.abs(area) / 2 / (w * h);
    }

    // The edge turns only while it crosses the panel, from the bottom-left corner to the
    // top-right one. Near the point it sweeps little of the panel per degree, far from it a
    // lot, so the keyframes are set by area: the panel fills (or empties) at the pace the
    // easing gives, never in a leap.
    function sweepKeyframes() {
        var w = window.innerWidth, h = window.innerHeight, p = sweepPivot(), degrees = 180 / Math.PI, steps = 16, frames = [];
        var from = Math.atan2(p[1] - h, p[0]) * degrees - 1, to = Math.atan2(p[1], p[0] - w) * degrees + 1;
        for (var i = 0; i <= steps; i++) {
            var lo = from, hi = to;
            for (var j = 0; j < 24; j++) { var mid = (lo + hi) / 2; if (sweptShare(mid) < i / steps) { lo = mid; } else { hi = mid; } }
            frames.push({ transform: 'rotate(' + (i === 0 ? from : i === steps ? to : (lo + hi) / 2) + 'deg)', offset: i / steps });
        }
        return frames;
    }

    function sweep(shape, coming) {
        var p = sweepPivot(), x = p[0], y = p[1], reach = Math.ceil(Math.hypot(x, y)) + 2;
        shape.style.inset = 'auto';
        shape.style.width = (reach * 2) + 'px';
        shape.style.height = reach + 'px';
        shape.style.left = (x - reach) + 'px';
        shape.style.top = (coming ? y : y - reach) + 'px';
        shape.style.transformOrigin = coming ? '50% 0' : '50% 100%';
    }

    function transitionName(name) {
        return name && transitions[name] && ! reducedMotion ? name : null;
    }

    function colorFor(mode) {
        return isDark(mode) ? '#000' : '#fff';
    }

    // Web fonts the new theme brings, or 400 ms, whichever comes first, so text does not swap
    // its font while the cover lifts.
    function fontsReady() {
        if (! document.fonts || ! document.fonts.ready) { return Promise.resolve(); }
        return Promise.race([document.fonts.ready, new Promise(function (resolve) { setTimeout(resolve, 400); })]);
    }

    function nextFrame() {
        return new Promise(function (resolve) { requestAnimationFrame(function () { requestAnimationFrame(resolve); }); });
    }

    // Resolves once the page has stopped changing under the cover: no DOM change for
    // quietFor ms (a Livewire response, a lazy widget, the reopened modal), then two frames
    // in a row on time, so the reveal starts on a browser with nothing else to do. Gives up
    // waiting after cap ms.
    function settled(quietFor, cap) {
        return new Promise(function (resolve) {
            var quiet = null, limit = null, done = false;
            var observer = new MutationObserver(function (records) {
                var ours = records.every(function (record) {
                    return (layer && layer.contains(record.target)) || (record.target === html && /^data-nitpick-/.test(record.attributeName || ''));
                });
                if (! ours) { clearTimeout(quiet); quiet = setTimeout(steady, quietFor); }
            });
            function finish() {
                if (done) { return; }
                done = true;
                observer.disconnect();
                clearTimeout(quiet);
                clearTimeout(limit);
                resolve();
            }
            function steady() {
                observer.disconnect();
                var last = null, onTime = 0;
                requestAnimationFrame(function frame(now) {
                    if (done) { return; }
                    if (last !== null) { onTime = now - last < 24 ? onTime + 1 : 0; }
                    last = now;
                    if (onTime >= 2) { return finish(); }
                    requestAnimationFrame(frame);
                });
            }
            observer.observe(html, { subtree: true, childList: true, attributes: true, characterData: true });
            quiet = setTimeout(steady, quietFor);
            limit = setTimeout(finish, cap);
        });
    }

    var layer = null;
    var coverColor = null;

    // Holds the panel's own CSS transitions while a swap runs: without it, every element
    // eases from the old theme's colours to the new ones as the cover lifts, which reads as
    // the reveal fading.
    function still(on) {
        if (on) { html.setAttribute('data-nitpick-still', ''); } else { html.removeAttribute('data-nitpick-still'); }
    }

    // Runs the cover in (coming) or out. Resolves when it is done.
    function animate(name, coming) {
        var transition = transitions[name];
        if (! layer) {
            layer = document.createElement('div');
            layer.id = 'nitpick-cover-layer';
            layer.setAttribute('aria-hidden', 'true');
            layer.appendChild(document.createElement('div'));
        }
        var shape = layer.firstChild;
        shape.removeAttribute('style');
        shape.style.background = coverColor;
        if (transition.shape) { transition.shape(shape, coming); }
        (document.body || html).appendChild(layer);
        // The static cover of a reloaded page hands over to the layer in the same frame.
        html.removeAttribute('data-nitpick-cover');
        if (typeof shape.animate !== 'function') {
            if (! coming) { layer.remove(); }
            return Promise.resolve();
        }
        var animation = shape.animate(transition.keyframes(coming), {
            duration: coming ? transition.in : transition.out,
            // A sine in-out both ways: it starts and settles gently, and its fastest frame
            // moves the cover not much more than an even pace would.
            easing: 'cubic-bezier(0.37, 0, 0.63, 1)',
            fill: 'both'
        });
        return animation.finished.then(function () { if (! coming) { layer.remove(); } });
    }

    // The stylesheets a pick swapped, loaded or given up on after 1.5 s.
    function loaded(links) {
        var loads = links.map(function (link) {
            return new Promise(function (resolve) {
                link.addEventListener('load', resolve, { once: true });
                link.addEventListener('error', resolve, { once: true });
            });
        });
        return Promise.race([Promise.all(loads), new Promise(function (resolve) { setTimeout(resolve, 1500); })]);
    }

    function pickerInModal() {
        return !! document.querySelector('.fi-modal .nitpick-picker');
    }

    // Opens the Appearance modal from its user-menu item, and resolves once the picker
    // shows (or after 2 s).
    function reopenPicker() {
        var trigger = Array.prototype.filter.call(document.querySelectorAll('button, a'), function (element) {
            return (element.getAttribute('wire:click') || '').indexOf('nitpick-appearance') !== -1;
        })[0];
        if (! trigger) { return Promise.resolve(); }
        trigger.click();
        return new Promise(function (resolve) {
            var waited = 0;
            (function check() {
                if (pickerInModal() || waited >= 2000) { return resolve(); }
                waited += 50;
                setTimeout(check, 50);
            })();
        });
    }

    stamp();
    document.addEventListener('livewire:navigated', stamp);

    // A page that a pick reloaded: start under the cover, and lift it once the page is
    // whole: Livewire started, the modal open again when the pick came from it (without its
    // fade), lazy widgets loaded and nothing else changing. Never longer than 2.5 s.
    var reloaded = null;
    try { reloaded = JSON.parse(sessionStorage.getItem(note) || 'null'); sessionStorage.removeItem(note); } catch (e) {}
    if (reloaded && Date.now() - reloaded.at < 10000) {
        var reveal = transitionName(reloaded.transition);
        var lifted = false;
        var started = new Promise(function (resolve) {
            if (window.Livewire && window.Livewire.all && window.Livewire.all().length) { return resolve(); }
            document.addEventListener('livewire:initialized', resolve, { once: true });
        });
        var reopened = null;
        var lift = function () {
            if (lifted) { return; }
            var whole = Promise.all([started, reopened, fontsReady()]);
            Promise.race([whole, new Promise(function (resolve) { setTimeout(resolve, 1800); })])
                .then(function () { return settled(120, 700); })
                .then(function () {
                    if (lifted) { return; }
                    lifted = true;
                    return reveal ? animate(reveal, false) : null;
                })
                .then(function () { still(false); });
        };
        if (reveal) {
            coverColor = reloaded.color;
            html.style.setProperty('--nitpick-cover-color', reloaded.color);
            html.setAttribute('data-nitpick-cover', '');
            still(true);
            // Never leave the panel covered: lift it after 4 s whatever happens.
            setTimeout(function () { if (! lifted) { lifted = true; html.removeAttribute('data-nitpick-cover'); } still(false); }, 4000);
        }
        // The user-menu item works once Livewire has started.
        if (reloaded.reopen) { reopened = started.then(reopenPicker); }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', lift, { once: true }); } else { setTimeout(lift, 0); }
    }

    var covered = null;
    var uncover = null;
    var swap = null;

    window.Nitpick = {
        current: function () { return html.getAttribute('data-nitpick-theme'); },
        list: function () { return manifest ? Object.keys(manifest.themes) : []; },
        transition: function () { return transitionName(manifest && manifest.transition); },
        // Changes the theme in the head without a transition. False for a theme that shows
        // only after a page load.
        preview: function (slug) {
            if (! manifest || ! manifest.themes[slug]) { return false; }
            return apply(slug) !== null;
        },
        // Changes the theme with the panel's transition: cover, change, lift. A theme that shows
        // only after a page load is covered only when a reload() follows ({ reload: true }, as the
        // picker passes when it saves the pick); otherwise only its light/dark mode is taken, as
        // in preview(). A pick while the cover is up changes the running swap's theme instead of
        // starting another cover over it.
        pick: function (slug, options) {
            var name = this.transition();
            if (! name || ! manifest || ! manifest.themes[slug]) { return this.preview(slug); }
            if (swap) { swap.slug = slug; return true; }
            if (needsReload(slug) && ! (options && options.reload)) { return this.preview(slug); }
            swap = { slug: slug };
            coverColor = colorFor(manifest.themes[slug].mode);
            covered = animate(name, true);
            covered.then(function change() {
                still(true);
                var target = swap.slug;
                var changed = apply(target);
                var lift = function () {
                    // Picked again under the cover: change to that theme before lifting.
                    if (swap.slug !== target) { return change(); }
                    swap = null;
                    covered = null;
                    return animate(name, false).then(function () { still(false); });
                };
                if (changed === null) {
                    // The picker calls reload() once the pick is saved; lift the cover if it never does.
                    uncover = setTimeout(lift, 5000);
                    return;
                }
                // Lift once the new theme has painted and the pick has saved, so the reveal never
                // starts mid-restyle.
                loaded(changed).then(fontsReady).then(function () { return settled(80, 900); }).then(lift);
            });
            return true;
        },
        // Applies an accent colour (CSS from the server: the palette as --primary-* variables), or none,
        // in the style block the head already holds, so the app's CSP nonce still covers it.
        accent: function (css) {
            var style = document.getElementById('nitpick-accent');
            if (style) { style.textContent = css || ''; }
            document.dispatchEvent(new CustomEvent('nitpick:accent-changed', { detail: { css: css || '' } }));
        },
        // Reloads the page once the cover is in, so the next page starts under it.
        reload: function () {
            var name = this.transition();
            var reopen = pickerInModal();
            (covered || Promise.resolve()).then(function () {
                clearTimeout(uncover);
                try {
                    sessionStorage.setItem(note, JSON.stringify({
                        transition: name,
                        color: coverColor || colorFor(null),
                        reopen: reopen,
                        at: Date.now()
                    }));
                } catch (e) {}
                window.location.reload();
            });
        }
    };
})();
