/* eslint-disable no-undef */
/**
 * GutSlider admin dashboard.
 *
 * Staged-change editing: toggles update the UI immediately, a save bar
 * collects the diff, and one request persists everything.
 */
(function () {
    'use strict';

    var settings = window.gutslider || {};
    var i18n = settings.i18n || {};
    var app = document.querySelector('.gs-app');

    if (!app) {
        return;
    }

    /* ----------------------------------------------------------------
     * Utilities
     * -------------------------------------------------------------- */

    function $(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function $$(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function sprintf(template, values) {
        var i = 0;
        return String(template)
            .replace(/%(\d)\$d/g, function (match, index) {
                return values[parseInt(index, 10) - 1];
            })
            .replace(/%d|%s/g, function () {
                return values[i++];
            });
    }

    function post(endpoint, body) {
        return window
            .fetch(settings.restUrl + endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': settings.nonce
                },
                body: JSON.stringify(body || {})
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            });
    }

    /* ----------------------------------------------------------------
     * Toasts
     * -------------------------------------------------------------- */

    var ICON_OK = '<path d="m5 13 4 4L19 7"/>';
    var ICON_ERR = '<path d="M18 6 6 18M6 6l12 12"/>';

    function toast(message, type) {
        var host = $('[data-gs-toasts]');

        if (!host) {
            return;
        }

        var el = document.createElement('div');
        el.className = 'gs-toast' + (type === 'error' ? ' gs-toast--error' : '');
        el.setAttribute('role', 'status');
        el.innerHTML =
            '<span class="gs-toast__icon"><svg class="gs-icon" viewBox="0 0 24 24" fill="none" ' +
            'stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">' +
            (type === 'error' ? ICON_ERR : ICON_OK) +
            '</svg></span><span></span>';
        el.lastChild.textContent = message;

        host.appendChild(el);

        window.setTimeout(function () {
            el.classList.add('is-out');
            window.setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 240);
        }, 2600);
    }

    /* ----------------------------------------------------------------
     * Colour scheme
     * -------------------------------------------------------------- */

    (function theme() {
        var KEY = 'gutslider-theme';
        var stored = null;

        try {
            stored = window.localStorage.getItem(KEY);
        } catch (e) {
            stored = null;
        }

        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var mode = stored || (prefersDark ? 'dark' : 'light');

        app.setAttribute('data-gs-theme', mode);

        var button = $('[data-gs-theme-toggle]');

        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            mode = app.getAttribute('data-gs-theme') === 'dark' ? 'light' : 'dark';
            app.setAttribute('data-gs-theme', mode);

            try {
                window.localStorage.setItem(KEY, mode);
            } catch (e) {
                /* Storage unavailable — the choice just will not persist. */
            }
        });
    })();

    /* ----------------------------------------------------------------
     * Walkthrough — a facade, so YouTube only loads if it is asked for
     * -------------------------------------------------------------- */

    (function walkthrough() {
        var trigger = $('[data-gs-video]');

        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', function () {
            var frame = document.createElement('iframe');

            frame.src = trigger.getAttribute('data-gs-video');
            frame.title = trigger.getAttribute('data-gs-video-title') || '';
            frame.allow =
                'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            frame.allowFullscreen = true;

            trigger.innerHTML = '';
            trigger.appendChild(frame);
            trigger.disabled = true;
            trigger.style.cursor = 'default';
        });
    })();

    /* ----------------------------------------------------------------
     * Save bar — shared by the blocks and settings screens
     * -------------------------------------------------------------- */

    function createSaveBar(handler) {
        var bar = $('[data-gs-savebar]');
        var text = $('[data-gs-savebar-text]');
        var saveBtn = $('[data-gs-save]');
        var discardBtn = $('[data-gs-discard]');
        var kbd = $('[data-gs-save-kbd]');
        var count = 0;
        var onDiscard = function () {};

        if (!bar) {
            return null;
        }

        if (kbd && navigator.platform.indexOf('Mac') === -1) {
            kbd.textContent = 'Ctrl+S';
        }

        function render(next) {
            count = next;

            if (count > 0) {
                text.textContent = sprintf(count === 1 ? i18n.changeSingle : i18n.changePlural, [count]);
                bar.classList.add('is-visible');
                bar.setAttribute('aria-hidden', 'false');
            } else {
                bar.classList.remove('is-visible');
                bar.setAttribute('aria-hidden', 'true');
            }
        }

        function save() {
            if (count === 0 || saveBtn.classList.contains('is-busy')) {
                return;
            }

            saveBtn.classList.add('is-busy');

            handler()
                .then(function () {
                    render(0);
                    toast(i18n.saved);
                })
                .catch(function () {
                    toast(i18n.saveFailed, 'error');
                })
                .then(function () {
                    saveBtn.classList.remove('is-busy');
                });
        }

        saveBtn.addEventListener('click', save);

        discardBtn.addEventListener('click', function () {
            onDiscard();
            render(0);
            toast(i18n.discarded);
        });

        document.addEventListener('keydown', function (event) {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                if (count > 0) {
                    event.preventDefault();
                    save();
                }
            }
        });

        window.addEventListener('beforeunload', function (event) {
            if (count > 0) {
                event.preventDefault();
                event.returnValue = i18n.leaveWarning;
                return i18n.leaveWarning;
            }

            return undefined;
        });

        return {
            update: render,
            onDiscard: function (fn) {
                onDiscard = fn;
            }
        };
    }

    /* ----------------------------------------------------------------
     * Blocks screen
     * -------------------------------------------------------------- */

    (function blocksScreen() {
        var screen = $('[data-gs-blocks]');

        if (!screen) {
            return;
        }

        var cards = $$('[data-gs-block]', screen);
        var searchInput = $('[data-gs-search]', screen);
        var counter = $('[data-gs-counter]', screen);
        var summary = $('[data-gs-summary]', screen);
        var metricValue = $('[data-gs-metric-enabled]', screen);
        var metricNote = $('[data-gs-metric-note]', screen);
        var empty = $('[data-gs-empty]', screen);
        var grid = $('[data-gs-grid]', screen);
        var filter = 'all';
        var query = '';

        var freeTotal = cards.filter(function (card) {
            return card.getAttribute('data-gs-tier') === 'free';
        }).length;
        var proTotal = cards.length - freeTotal;

        var state = {};
        var initial = {};

        cards.forEach(function (card) {
            var name = card.getAttribute('data-gs-block');
            var on = card.getAttribute('data-gs-initial') === '1';
            initial[name] = on;
            state[name] = on;
        });

        var bar = createSaveBar(function () {
            var changed = diff();

            return post('dashboard/blocks', { blocks: changed }).then(function () {
                Object.keys(changed).forEach(function (name) {
                    initial[name] = changed[name];
                });
                markDirty();
            });
        });

        function diff() {
            var changed = {};

            Object.keys(state).forEach(function (name) {
                if (state[name] !== initial[name]) {
                    changed[name] = state[name];
                }
            });

            return changed;
        }

        function markDirty() {
            var changed = diff();

            cards.forEach(function (card) {
                var name = card.getAttribute('data-gs-block');
                card.classList.toggle('is-dirty', Object.prototype.hasOwnProperty.call(changed, name));
            });

            if (bar) {
                bar.update(Object.keys(changed).length);
            }
        }

        function updateCount() {
            var enabled = 0;

            cards.forEach(function (card) {
                if (state[card.getAttribute('data-gs-block')]) {
                    enabled += 1;
                }
            });

            if (counter) {
                counter.textContent = sprintf(i18n.enabledCount, [enabled, cards.length]);
            }

            if (summary) {
                summary.textContent = sprintf(i18n.blockSummary, [
                    freeTotal,
                    proTotal,
                    enabled,
                    cards.length
                ]);
            }

            if (metricValue) {
                metricValue.textContent = String(enabled);
            }

            if (metricNote) {
                var off = cards.length - enabled;
                metricNote.textContent = off > 0 ? sprintf(i18n.someDisabled, [off]) : i18n.allEnabled;
            }
        }

        function applyFilters() {
            var visible = 0;

            cards.forEach(function (card) {
                var name = card.getAttribute('data-gs-block');
                var tier = card.getAttribute('data-gs-tier');
                var haystack = card.getAttribute('data-gs-name') || '';
                var on = !!state[name];

                var matchesQuery = query === '' || haystack.indexOf(query) !== -1;
                var matchesFilter =
                    filter === 'all' ||
                    (filter === 'free' && tier === 'free') ||
                    (filter === 'pro' && tier === 'pro') ||
                    (filter === 'enabled' && on) ||
                    (filter === 'disabled' && !on);

                var show = matchesQuery && matchesFilter;
                card.classList.toggle('is-hidden', !show);

                if (show) {
                    visible += 1;
                }
            });

            if (empty) {
                empty.hidden = visible !== 0;
            }

            if (grid) {
                grid.hidden = visible === 0;
            }
        }

        function setToggle(card, on) {
            var name = card.getAttribute('data-gs-block');
            var input = $('[data-gs-toggle]', card);

            state[name] = on;

            if (input) {
                input.checked = on;
            }

            card.classList.toggle('is-on', on);
            card.classList.toggle('is-off', !on);
        }

        // Toggles.
        $$('[data-gs-toggle]', screen).forEach(function (input) {
            input.addEventListener('change', function () {
                var card = input.closest('[data-gs-block]');
                setToggle(card, input.checked);
                markDirty();
                updateCount();
                applyFilters();
            });
        });

        // Search.
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                query = searchInput.value.trim().toLowerCase();
                applyFilters();
            });

            document.addEventListener('keydown', function (event) {
                var tag = document.activeElement ? document.activeElement.tagName : '';

                if (event.key === '/' && tag !== 'INPUT' && tag !== 'TEXTAREA') {
                    event.preventDefault();
                    searchInput.focus();
                }

                if (event.key === 'Escape' && document.activeElement === searchInput) {
                    searchInput.value = '';
                    query = '';
                    applyFilters();
                }
            });
        }

        // Filter segments.
        $$('[data-gs-filter]', screen).forEach(function (button) {
            button.addEventListener('click', function () {
                filter = button.getAttribute('data-gs-filter');

                $$('[data-gs-filter]', screen).forEach(function (other) {
                    var active = other === button;
                    other.classList.toggle('is-active', active);
                    other.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                applyFilters();
            });
        });

        // Clear filters from the empty state.
        var clearBtn = $('[data-gs-clear-filters]', screen);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                query = '';
                filter = 'all';

                if (searchInput) {
                    searchInput.value = '';
                }

                $$('[data-gs-filter]', screen).forEach(function (other) {
                    var active = other.getAttribute('data-gs-filter') === 'all';
                    other.classList.toggle('is-active', active);
                    other.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                applyFilters();
            });
        }

        // Bulk actions apply to whatever is currently visible.
        $$('[data-gs-bulk]', screen).forEach(function (button) {
            button.addEventListener('click', function () {
                var on = button.getAttribute('data-gs-bulk') === 'on';

                cards.forEach(function (card) {
                    if (card.classList.contains('is-hidden') || card.getAttribute('data-gs-locked') === '1') {
                        return;
                    }

                    setToggle(card, on);
                });

                markDirty();
                updateCount();
                applyFilters();
            });
        });

        if (bar) {
            bar.onDiscard(function () {
                cards.forEach(function (card) {
                    setToggle(card, initial[card.getAttribute('data-gs-block')]);
                    card.classList.remove('is-dirty');
                });

                updateCount();
                applyFilters();
            });
        }

        updateCount();
        applyFilters();
    })();

    /* ----------------------------------------------------------------
     * Settings screen
     * -------------------------------------------------------------- */

    (function settingsScreen() {
        var screen = $('[data-gs-settings]');

        if (!screen) {
            return;
        }

        var inputs = $$('[data-gs-setting]', screen);
        var initial = {};
        var state = {};

        inputs.forEach(function (input) {
            var key = input.getAttribute('data-gs-setting');

            if (input.type === 'radio') {
                initial[key] = input.getAttribute('data-gs-initial');

                if (input.checked) {
                    state[key] = input.value;
                }
            } else {
                initial[key] = input.getAttribute('data-gs-initial') === '1';
                state[key] = input.checked;
            }
        });

        var bar = createSaveBar(function () {
            var changed = diff();

            return post('dashboard/settings', { settings: changed }).then(function () {
                Object.keys(changed).forEach(function (key) {
                    initial[key] = changed[key];
                });
            });
        });

        function diff() {
            var changed = {};

            Object.keys(state).forEach(function (key) {
                if (state[key] !== initial[key]) {
                    changed[key] = state[key];
                }
            });

            return changed;
        }

        function markDirty() {
            if (bar) {
                bar.update(Object.keys(diff()).length);
            }
        }

        function syncRadioCards() {
            $$('.gs-radio-card', screen).forEach(function (card) {
                var input = $('input', card);
                card.classList.toggle('is-active', !!input && input.checked);
            });
        }

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var key = input.getAttribute('data-gs-setting');

                if (input.type === 'radio') {
                    if (!input.checked) {
                        return;
                    }
                    state[key] = input.value;
                    syncRadioCards();
                } else {
                    state[key] = input.checked;
                }

                markDirty();
            });
        });

        if (bar) {
            bar.onDiscard(function () {
                inputs.forEach(function (input) {
                    var key = input.getAttribute('data-gs-setting');

                    if (input.type === 'radio') {
                        input.checked = input.value === initial[key];
                        state[key] = initial[key];
                    } else {
                        input.checked = initial[key];
                        state[key] = initial[key];
                    }
                });

                syncRadioCards();
            });
        }

        // Clear generated stylesheets.
        var clearCache = $('[data-gs-clear-cache]', screen);

        if (clearCache) {
            clearCache.addEventListener('click', function () {
                if (clearCache.classList.contains('is-busy')) {
                    return;
                }

                clearCache.classList.add('is-busy');

                post('dashboard/clear-cache')
                    .then(function () {
                        toast(i18n.cacheCleared);
                    })
                    .catch(function () {
                        toast(i18n.saveFailed, 'error');
                    })
                    .then(function () {
                        clearCache.classList.remove('is-busy');
                    });
            });
        }

        /*
         * Section navigation.
         *
         * Deliberately not an IntersectionObserver: the trailing sections are
         * often too short to ever cross a fixed detection band, so they could
         * never become active. Instead, the current section is the last one
         * whose top has passed the offset line, and the bottom of the page
         * always owns the final section.
         */
        var sections = $$('[data-gs-sidenav]', screen)
            .map(function (link) {
                var panel = $(link.getAttribute('href'), screen);
                return panel ? { link: link, panel: panel } : null;
            })
            .filter(Boolean);

        if (sections.length) {
            // Distance from the viewport top, clearing the sticky bar.
            var SPY_OFFSET = 140;
            var spyFrame = null;
            var spyLockedUntil = 0;

            var setActiveSection = function (link) {
                sections.forEach(function (section) {
                    section.link.classList.toggle('is-active', section.link === link);
                });
            };

            var syncSpy = function () {
                spyFrame = null;

                // A click owns the highlight until its smooth scroll settles.
                if (Date.now() < spyLockedUntil) {
                    return;
                }

                var scrollable = document.documentElement.scrollHeight - window.innerHeight;
                var current = sections[0];

                if (scrollable > 4 && window.pageYOffset >= scrollable - 2) {
                    current = sections[sections.length - 1];
                } else {
                    sections.forEach(function (section) {
                        if (section.panel.getBoundingClientRect().top <= SPY_OFFSET) {
                            current = section;
                        }
                    });
                }

                setActiveSection(current.link);
            };

            var queueSpy = function () {
                if (spyFrame === null) {
                    spyFrame = window.requestAnimationFrame(syncSpy);
                }
            };

            sections.forEach(function (section) {
                section.link.addEventListener('click', function (event) {
                    event.preventDefault();
                    setActiveSection(section.link);
                    spyLockedUntil = Date.now() + 700;
                    section.panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            window.addEventListener('scroll', queueSpy, { passive: true });
            window.addEventListener('resize', queueSpy);
            syncSpy();
        }

        syncRadioCards();
    })();
})();
