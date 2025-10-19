/*! CodeCorn™ Select2 Compat — BRIDGE ( PRE + POST ) */
// @ts-nocheck
(function ($) {
    if (!$.fn) return;

    // Flag debug da PHP
    window.CC_S2_DEBUG = !!(window.CC_S2_OPTS && window.CC_S2_OPTS.debug);
    window.CC_S2 = window.CC_S2 || {};
    window.CC_S2.version = (window.CC_S2_META && CC_S2_META.version) || 'dev';
    window.CC_S2.vendor  = (window.CC_S2_META && CC_S2_META.vendor)  || null;
    try {
    Object.defineProperty(window, 'CC_S2_VERSION', { value: CC_S2_META.version, writable: false });
    Object.defineProperty(window, 'CC_S2_VENDOR',  { value: CC_S2_META.vendor,  writable: false });
    } catch(_){ window.CC_S2_VERSION = CC_S2_META.version; window.CC_S2_VENDOR = CC_S2_META.vendor; }
    // ===== PRE =====
    try {
        document.documentElement.classList.add('cc-s2-booting');
    } catch (e) {}

    // Logger “silenziosi”
    if (!window.CC_LOGGER_READY) {
        window.CC_LOGGER_READY = true;
        window.CC_COLORS = { ok: '#9fe870', warn: '#f3b44a', err: '#e85959', info: '#7ac8ff', gold: '#b69b6a', bg1: '#111', bg3: '#333' };
        window.CC_BADGE = function (mod, msg, colorKey) {
            if (!window.CC_S2_DEBUG) return;
            var c = window.CC_COLORS[colorKey] || colorKey || window.CC_COLORS.gold;
            var tag = '%c CodeCorn™ ' + mod + ' %c ' + msg + ' %c';
            var css1 = 'background:' + window.CC_COLORS.bg1 + ';color:#fff;padding:3px 6px;border-radius:4px 0 0 4px;';
            var css2 = 'background:' + c + ';color:#000;padding:3px 6px;';
            var css3 = 'background:' + window.CC_COLORS.bg3 + ';color:#fff;padding:3px 6px;border-radius:0 4px 4px 0;';
            try {
                console.log(tag, css1, css2, css3);
            } catch (_) {}
        };
        window.CC_LOG = function () {
            if (!window.CC_S2_DEBUG) return;
            try {
                var parts = Array.prototype.map.call(arguments, function (a) {
                    if (a && (a.nodeType || a.jquery)) return '[DOM]';
                    if (typeof a === 'object') {
                        try {
                            return JSON.stringify(a);
                        } catch (_) {
                            return '[Object]';
                        }
                    }
                    return String(a);
                });
                window.CC_BADGE('Select2 Compat', parts.join(' '), 'ok');
            } catch (_) {}
        };
    }

    // Salva eventuale select2 globale
    window._ccSelect2Legacy = window._ccSelect2Legacy || ($.fn.select2 ? $.fn.select2 : null);

    // ===== POST =====
    var vendorPlugin = $.fn.select2 || null;
    CC_LOG('post boot → vendor select2 =', !!vendorPlugin);

    // Ripristina il namespace globale oppure liberalo
    if (window._ccSelect2Legacy) {
        $.fn.select2 = window._ccSelect2Legacy;
        CC_LOG('legacy select2 ripristinato sul namespace globale');
    } else {
        try {
            delete $.fn.select2;
        } catch (e) {
            $.fn.select2 = undefined;
        }
        CC_LOG('namespace globale select2 liberato');
    }

    // Espone l’istanza isolata
    $.fn.ccSelect2 = vendorPlugin;
    window.CC_S2_READY = !!$.fn.ccSelect2;
    CC_LOG('ccSelect2 ready =', window.CC_S2_READY);

    // Heuristics versione
    (function () {
        var v = null,
            fam = '4.0.13',
            full = false;
        try {
            full = !!($.fn.ccSelect2 && $.fn.ccSelect2.amd && $.fn.ccSelect2.defaults && $.fn.ccSelect2.defaults.set);
            if ($.fn.ccSelect2 && $.fn.ccSelect2.amd) {
                var req = $.fn.ccSelect2.amd.require;
                if (typeof req === 'function') {
                    try {
                        var Core = req('select2/core');
                        if (Core && Core.prototype) v = Core.prototype.version || Core.prototype._version || null;
                    } catch (_) {}
                }
            }
            if (!v) v = full ? '4.0+ (full)' : '4.0+ (lite)';
        } catch (_) {}
        window.CC_S2_INFO = { version: v, family: fam, full: full };
        window.CC_BADGE && window.CC_BADGE('Select2 Compat', (v || fam) + ' ' + (full ? 'FULL' : 'LITE'), 'ok');
    })();

    // Self-heal
    function ensureCcAlias() {
        if (!$.fn) return;
        if (!$.fn.ccSelect2 && $.fn.select2) {
            $.fn.ccSelect2 = $.fn.select2;
            window.CC_S2_READY = true;
            CC_LOG('self-heal: ccSelect2 re-bound dal globale');
            if (window.CC_S2 && typeof window.CC_S2.scan === 'function') window.CC_S2.scan(document);
        }
    }
    ensureCcAlias();
    document.addEventListener('DOMContentLoaded', ensureCcAlias);
    window.addEventListener('load', ensureCcAlias);
    (function beat(n) {
        ensureCcAlias();
        if (n > 8) return;
        setTimeout(function () {
            beat(n + 1);
        }, 240);
    })(0);

    // Rimuove anti-flicker nel frame successivo
    try {
        requestAnimationFrame(function () {
            document.documentElement.classList.remove('cc-s2-booting');
        });
    } catch (e) {
        document.documentElement.classList.remove('cc-s2-booting');
    }
})(jQuery);
