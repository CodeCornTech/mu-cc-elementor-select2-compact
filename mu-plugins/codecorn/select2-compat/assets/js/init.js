/*! CodeCorn™ Select2 Compat — INIT */
// @ts-nocheck
(function ($) {
    if (!$.fn) return;

    var INited = new WeakSet();

    function isReady() {
        return !!($.fn && $.fn.ccSelect2);
    }

    // Parent corretto per il dropdown (modal-safe)
    function getDropdownParent(el) {
        var $el = jQuery(el);

        // Elementor Popup: usa il contenuto del dialog, non solo il wrapper modale
        var $popup = $el.closest('.elementor-popup-modal');
        if ($popup.length) {
            var $content = $popup.find('.dialog-widget-content').first();
            if ($content.length) return $content;
            return $popup; // fallback: meglio il modal che body
        }

        // Bootstrap / generico
        var $bs = $el.closest('.modal.show, .modal');
        if ($bs.length) {
            // prova il dialog che di solito ha stacking/overflow corretti
            var $dlg = $bs.find('.modal-dialog, .modal-content').first();
            return $dlg.length ? $dlg : $bs;
        }

        // fallback globale
        return jQuery('body');
    }

    // ===== I18N loader =====
    var I18N = window.CC_S2_I18N || { base: '', default: 'en' };
    var _i18nCache = Object.create(null);

    function normLang(code) {
        if (!code) return 'en';
        code = String(code).trim().replace(/_/g, '-');
        if (code.indexOf('-') > -1) {
            var p = code.split('-');
            return p[0].toLowerCase() + '-' + p[1].toUpperCase();
        }
        return code.toLowerCase();
    }

    function ensureLang(code) {
        code = normLang(code || I18N.default || 'en');
        if (code === 'en') return Promise.resolve('en');
        if (_i18nCache[code] === 'ready') return Promise.resolve(code);
        if (_i18nCache[code] && _i18nCache[code].then) return _i18nCache[code];

        var url = (I18N.base || '') + code + '.js';
        _i18nCache[code] = new Promise(function (resolve) {
            var s = document.createElement('script');
            s.src = url;
            s.async = true;
            s.onload = function () {
                _i18nCache[code] = 'ready';
                resolve(code);
            };
            s.onerror = function () {
                console && console.warn && console.warn('[CC-S2] i18n load fail', code, url);
                _i18nCache[code] = 'fail';
                resolve('en');
            };
            document.head.appendChild(s);
        });
        return _i18nCache[code];
    }
    //prettier-ignore
    const fb_it ={ errorLoading: function () { return 'Impossibile caricare i risultati.'; }, inputTooLong: function (a) { var n = a.input.length - a.maximum; return 'Cancella ' + n + ' caratter' + (n > 1 ? 'i' : 'e'); }, inputTooShort: function (a) { var n = a.minimum - a.input.length; return 'Inserisci ancora ' + n + ' caratter' + (n > 1 ? 'i' : 'e'); }, loadingMore: function () { return 'Carico altri risultati…'; }, maximumSelected: function (a) { return 'Puoi selezionare al massimo ' + a.maximum + ' elementi'; }, noResults: function () { return 'Nessun risultato'; }, searching: function () { return 'Ricerca…'; }, removeAllItems: function () { return 'Rimuovi tutti gli elementi'; }, };
    const df_set = {
        placeholder: 'Scegli un valore',
        containerCssClass: 'cc-s2-container',
        dropdownCssClass: 'cc-s2-dropdown',
    };
    function enhance(el) {
        if (!isReady() || !el || INited.has(el) || el.classList.contains('select2-hidden-accessible')) return;
        var $el = $(el);
        if ($el.is('[data-cc-s2="off"]')) return;

        INited.add(el);

        var ph = $el.attr('data-placeholder');
        if (!ph) {
            var $first = $el.find('option[value=""], option:not([value])').first();
            if ($first.length) ph = ($first.text() || '').trim();
        }

        // lingua preferita per questa istanza
        var langPref = normLang($el.data('lang') || $el.attr('lang') || window.CC_S2_DEFAULT_LANG || I18N.default || 'en');

        var opts = {
            width: '100%',
            placeholder: ph || df_set['placeholder'],
            allowClear: !!ph,
            closeOnSelect: !$el.prop('multiple'),
            dropdownParent: getDropdownParent(el),
            containerCssClass: $el.data('container-css') || df_set['containerCssClass'],
            dropdownCssClass: $el.data('dropdown-css') || df_set['dropdownCssClass'],
            language: langPref,
        };

        //$el.ccSelect2(opts);
        ensureLang(langPref).then(function (actual) {
            //if (actual === 'en' && langPref === 'it') { opts.language = fb_it; } // DECOMMENTA PER USARE FB IT
            try {
                $el.ccSelect2(opts);
            } catch (e) {
                if (window.CC_S2_DEBUG && console && console.warn) console.warn('[CC-S2] init fail', e);
            }
        });

        // Focus search on open
        $el.on('select2:open', function () {
            var f = document.querySelector('.select2-container--open .select2-search__field');
            if (f) f.focus({ preventScroll: true });
        });

        // Reset sicuro al reset del form
        var form = $el.closest('form').get(0);
        if (form && !form.__ccS2ResetAttached) {
            form.__ccS2ResetAttached = true;
            form.addEventListener('reset', function () {
                $(form)
                    .find('select')
                    .each(function () {
                        if (this.classList.contains('select2-hidden-accessible')) $(this).val(null).trigger('change');
                    });
            });
        }
    }

    function qsa(root) {
        return (root || document).querySelectorAll('form.elementor-form select[id^="form-field-cc_s2_"]:not(.select2-hidden-accessible),' + 'form.elementor-form .elementor-field-type-select select[name^="form_fields[cc_s2_"]:not(.select2-hidden-accessible),' + 'select[data-cc-s2]:not([data-cc-s2="off"]):not(.select2-hidden-accessible),' + 'select.cc-s2:not(.select2-hidden-accessible),' + 'select[id^="cc_s2_"]:not(.select2-hidden-accessible)');
    }

    // Above-the-fold : attacco nel frame
    function instantATF() {
        if (!isReady()) return;
        var vh = window.innerHeight || 800;
        var nodes = qsa(document);
        for (var i = 0; i < nodes.length; i++) {
            var r = nodes[i].getBoundingClientRect();
            if (r.top < vh + 80) enhance(nodes[i]);
        }
        // Forza immediata per data-cc-s2="instant"
        var instant = document.querySelectorAll('select[data-cc-s2="instant"]');
        for (var k = 0; k < instant.length; k++) enhance(instant[k]);
    }
    if (document.readyState !== 'loading') requestAnimationFrame(instantATF);
    else
        document.addEventListener('DOMContentLoaded', function () {
            requestAnimationFrame(instantATF);
        });

    // IntersectionObserver : init lazy invisibile
    var io;
    try {
        io = new IntersectionObserver(
            function (entries) {
                if (!isReady()) return;
                entries.forEach(function (en) {
                    if (en.isIntersecting) {
                        requestAnimationFrame(function () {
                            enhance(en.target);
                            io.unobserve(en.target);
                        });
                    }
                });
            },
            { root: null, rootMargin: '128px 0px 128px 0px', threshold: 0.01 },
        );
    } catch (e) {
        io = null;
    }

    function observeAll(root) {
        var nodes = qsa(root);
        for (var i = 0; i < nodes.length; i++) {
            if (io) io.observe(nodes[i]);
            else enhance(nodes[i]);
        }
    }

    // Idle scan per off-screen profondo
    function idleScan() {
        var fn = function () {
            observeAll(document);
        };
        if ('requestIdleCallback' in window) window.requestIdleCallback(fn, { timeout: 1500 });
        else setTimeout(fn, 400);
    }

    // Hook generali
    jQuery(function () {
        idleScan();
    });
    // jQuery(document).on('elementor/popup/show', function (_, __, instance) {
    //     var root = instance && instance.$element && instance.$element[0];
    //     observeAll(root || document);
    // });
    jQuery(document)
        .off('elementor/popup/show.ccs2')
        .on('elementor/popup/show.ccs2', function (_, __, instance) {
            var root = instance && instance.$element && instance.$element[0];
            var $scope = jQuery(root || document);

            // individua i select target nel popup (stesso filtro della tua qsa)
            var $targets = $scope.find('form.elementor-form select[id^="form-field-cc_s2_"],' + 'form.elementor-form .elementor-field-type-select select[name^="form_fields[cc_s2_"],' + 'select[data-cc-s2]:not([data-cc-s2="off"]),' + 'select.cc-s2,' + 'select[id^="cc_s2_"]');

            $targets.each(function () {
                var $s = jQuery(this);

                // se già Select2, distruggi per ricreare con parent corretto
                if ($s.hasClass('select2-hidden-accessible')) {
                    try {
                        $s.ccSelect2('destroy');
                    } catch (e) {}
                    // ripulisci eventuali container residui
                    var $cont = $s.next('.select2.select2-container');
                    if ($cont.length) $cont.remove();
                }

                // forza recalcolo lingua + parent e re-init
                var ph = $s.attr('data-placeholder');
                if (!ph) {
                    var $f = $s.find('option[value=""], option:not([value])').first();
                    if ($f.length) ph = ($f.text() || '').trim();
                }
                var langPref = normLang($s.data('lang') || $s.attr('lang') || I18N.default || 'en');

                var opts = {
                    width: '100%',
                    placeholder: ph || df_set['placeholder'],
                    allowClear: !!ph,
                    closeOnSelect: !$s.prop('multiple'),
                    dropdownParent: getDropdownParent(this),
                    containerCssClass: $s.data('container-css') || df_set['containerCssClass'],
                    dropdownCssClass: $s.data('dropdown-css') || df_set['dropdownCssClass'],
                    language: langPref,
                };

                ensureLang(langPref).then(function () {
                    try {
                        $s.ccSelect2(opts);
                    } catch (e) {}
                });
            });
        });

    jQuery(document).on('ajaxComplete', function () {
        observeAll(document);
    });
    jQuery(document)
        .off('select2:open.ccs2')
        .on('select2:open.ccs2', function (e) {
            var ph = jQuery(e.target).data('search-placeholder') || jQuery(e.target).data('placeholder') || 'Digita per cercare un valore';
            jQuery('.select2-search__field').attr('placeholder', ph);
        });

    // MutationObserver ( debounce 80 ms )
    (function () {
        try {
            var tick = null,
                mo = new MutationObserver(function () {
                    if (tick) return;
                    tick = setTimeout(function () {
                        tick = null;
                        observeAll(document);
                    }, 80);
                });
            mo.observe(document.body, { childList: true, subtree: true });
        } catch (e) {}
    })();

    // API pubblica minima
    window.CC_S2 = window.CC_S2 || {};
    window.CC_S2.scan = function (root) {
        observeAll(root || document);
    };
})(jQuery);
