<?php
/**
 *  ╔══════════════════════════════════════════════════════════════════════╗
 *  ║  CodeCorn™ Select2 Compat (MU)                                       ║
 *  ║  Vendor-scoped Select2 for WordPress + Elementor (no conflicts).     ║
 *  ║  Exposes $.fn.ccSelect2 and preserves any existing $.fn.select2.     ║
 *  ╚══════════════════════════════════════════════════════════════════════╝
 *
 *  Plugin Name:  CodeCorn™ Select2 Compat (MU)
 *  Description:  Carica una Select2 vendorizzata senza collisioni e integra i select dei form Elementor (single/multiple).
 *  Author:       CodeCorn™ Technology
 *  Version:      1.1.58
 *  License:      MIT
 */

if (!defined('ABSPATH'))
  exit;

/** Paths vendors **/
function cc_s2_rel_base()
{
  return 'mu-plugins/codecorn/vendors/select2';
}
function cc_s2_url($rel)
{
  return content_url(ltrim($rel, '/'));
}

/** Enqueue: PRE → VENDOR → POST (no-collision) + INIT + CSS */
add_action('wp_enqueue_scripts', 'cc_s2_enqueue', 20);
add_action('elementor/editor/after_enqueue_scripts', 'cc_s2_enqueue', 20);
function cc_s2_enqueue()
{
  // debug toggle: define('CC_S2_DEBUG', true) in wp-config.php OR add_filter('cc_s2_debug','__return_true');
  $cc_s2_debug = apply_filters('cc_s2_debug', defined('CC_S2_DEBUG') ? CC_S2_DEBUG : false);

  // CSS (vendor + tweak compat Elementor / fallback globale)
  $css_url = cc_s2_url(cc_s2_rel_base() . '/css/select2.min.css');
  wp_enqueue_style('cc-s2-css', $css_url, [], '4.0.13');

  $tweak = <<<CSS
/* Elementor compat + full width */
.elementor-form .select2-container{ width:100% !important; }
.elementor-form .select2-selection--single .select2-selection__rendered{ line-height: 38px; }
.elementor-form .select2-selection--single{ height: 40px; }
.elementor-form .select2-selection--multiple{ min-height: 40px; }
.elementor-form .select2-selection__choice{ margin-top: 6px; }
/* Fallback globale (fuori Elementor) */
.select2-container{ width:100% !important; }
/* caret Elementor (per non avere doppie frecce) */
.select_container:after, .select_container:before, .select-caret-down-wrapper {
  display: none !important;
}
CSS;
  wp_add_inline_style('cc-s2-css', $tweak);

  if ($cc_s2_debug) {
    $badgeCss = <<<CSS
[data-cc-s2-pill]{
  position:fixed; right:10px; bottom:10px; z-index:2147483647;
  font:12px/1.4 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
  background:linear-gradient(135deg,#111,#333); color:#fff; padding:6px 10px;
  border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.25); letter-spacing:.2px;
}
CSS;
    wp_add_inline_style('cc-s2-css', $badgeCss);
  }

  // JS sandwich anti-collisione
  wp_register_script('cc-s2-pre', false, ['jquery'], '1.1.58', true);
  wp_register_script('cc-s2-vendor', cc_s2_url(cc_s2_rel_base() . '/js/select2.full.min.js'), ['jquery', 'cc-s2-pre'], '4.0.13', true);
  wp_register_script('cc-s2-post', false, ['jquery', 'cc-s2-vendor'], '1.1.58', true);

  // ✅ localize DOPO register, PRIMA dei blocchi inline
  wp_localize_script('cc-s2-post', 'CC_S2_OPTS', ['debug' => (bool) $cc_s2_debug]);

  // PRE: salva l’eventuale select2 legacy già esistente
  $pre = <<<'JS'
(function($){
  if (!$.fn) return;
  window._ccSelect2Legacy = $.fn.select2 ? $.fn.select2 : null;
  if (!window.CC_LOGGER_READY) {
    window.CC_LOGGER_READY = true;
    window.CC_COLORS = { ok:'#9fe870', warn:'#f3b44a', err:'#e85959', info:'#7ac8ff', gold:'#b69b6a', bg1:'#111', bg3:'#333' };
    window.CC_BADGE = function(mod, msg, colorKey){
      if (!window.CC_S2_DEBUG) return;
      const c = window.CC_COLORS[colorKey] || colorKey || window.CC_COLORS.gold;
      const tag = '%c CodeCorn™ ' + mod + ' %c ' + msg + ' %c';
      const css1 = 'background:' + window.CC_COLORS.bg1 + ';color:#fff;padding:3px 6px;border-radius:4px 0 0 4px;';
      const css2 = 'background:' + c + ';color:#000;padding:3px 6px;';
      const css3 = 'background:' + window.CC_COLORS.bg3 + ';color:#fff;padding:3px 6px;border-radius:0 4px 4px 0;';
      try { console.log(tag, css1, css2, css3); } catch(e) { console.log('[CodeCorn '+mod+']', msg); }
    };
    // rimpiazza il corpo di CC_LOG con:
    window.CC_LOG = function(){
      if (!window.CC_S2_DEBUG) return;
      try {
        var parts = Array.prototype.map.call(arguments, function(a){
          if (a && (a.nodeType || a.jquery)) return '[DOM]';
          if (typeof a === 'object') {
            try { return JSON.stringify(a); } catch(e){ return '[Object]'; }
          }
          return String(a);
        });
        window.CC_BADGE('Select2 Compat', parts.join(' '), 'ok');
      } catch(e){}
    };
  }
})(jQuery);
JS;
  wp_add_inline_script('cc-s2-pre', $pre);

  // POST: cattura il plugin del vendor → mappa su $.fn.ccSelect2, ripristina (o libera) $.fn.select2
  $post = <<<'JS'
// @ts-nocheck
(function ($) {
  if (!$.fn) return;

  // === DEBUG FLAG (da PHP via wp_localize_script) ===
  window.CC_S2_DEBUG = !!(window.CC_S2_OPTS && window.CC_S2_OPTS.debug);

  // usa i logger globali ovunque
  var BADGE = window.CC_BADGE || function(){};
  var LOG   = window.CC_LOG   || function(){};
  var CC_COLORS = window.CC_COLORS || {};

  // 1) cattura plugin vendor
  var vendorPlugin = $.fn.select2 || null;
  CC_LOG('post boot → vendor select2 =', !!vendorPlugin);

  // 2) ripristina eventuale legacy su $.fn.select2
  if (window._ccSelect2Legacy) {
    $.fn.select2 = window._ccSelect2Legacy;
    CC_LOG('legacy select2 ripristinato sul namespace globale');
  } else {
    try { delete $.fn.select2; } catch (e) { $.fn.select2 = undefined; }
    CC_LOG('namespace globale select2 liberato');
  }

  // 3) esponi istanza isolata
  $.fn.ccSelect2 = vendorPlugin;
  window.CC_S2_READY = !!$.fn.ccSelect2;
  CC_LOG('ccSelect2 ready =', window.CC_S2_READY);

  // 4) version heuristics
  function detectVersion(){
    var v=null, fam='4.0.13', full=false;
    try{
      full = !!($.fn.ccSelect2 && $.fn.ccSelect2.amd && $.fn.ccSelect2.defaults && $.fn.ccSelect2.defaults.set);
      if ($.fn.ccSelect2 && $.fn.ccSelect2.amd) {
        var req = $.fn.ccSelect2.amd.require;
        if (typeof req === 'function') {
          try {
            var Core = req('select2/core');
            if (Core && Core.prototype) v = Core.prototype.version || Core.prototype._version || null;
          } catch(e){}
        }
      }
      if (!v) v = full ? '4.0+ (full)' : '4.0+ (lite)';
    } catch(e){}
    return {version:v, family:fam, full:full};
  }
  var info = detectVersion();
  window.CC_S2_INFO = info;

  // 5) banner iniziale
  BADGE('Select2 Compat', (info.version || info.family) + ' ' + (info.full ? 'FULL' : 'LITE'),CC_COLORS.ok);

  // 6) self-heal: se qualcuno rimpiazza ccSelect2 → rebind
  function ensureCcAlias(){
    if (!$.fn) return;
    if (!$.fn.ccSelect2 && $.fn.select2) {
      $.fn.ccSelect2 = $.fn.select2;
      window.CC_S2_READY = true;
      CC_LOG('self-heal: ccSelect2 re-bound dal globale');
      // ➕ subito una passata
      if (window.CC_S2 && typeof window.CC_S2.scan === 'function') {
        window.CC_S2.scan(document);
      }
    }
  }
  ensureCcAlias();
  document.addEventListener('DOMContentLoaded', ensureCcAlias);
  window.addEventListener('load', ensureCcAlias);
  (function beat(n){ ensureCcAlias(); if (n>10) return; setTimeout(function(){ beat(n+1); }, 300); })(0);

})(jQuery);
JS;
  wp_add_inline_script('cc-s2-post', $post);

  // INIT: integra Elementor (frontend + editor, iframe safe), single/multiple
  wp_register_script('cc-s2-init', false, ['jquery', 'cc-s2-post'], '1.1.58', true);

  // ===== init completo con prefix #cc_s2_ + API CC_S2 =====
  $init = <<<'JS'
 (function($){
  if (!$.fn) return;
  // usa i logger globali ovunque
  var BADGE = window.CC_BADGE || function(){};
  var LOG   = window.CC_LOG   || function(){};
  var CC_COLORS = window.CC_COLORS || {};

  // --- guards / retry
  var READY_CHECK_MAX=60, READY_CHECK_WAIT=250; DNC_TMOUT=125;
  var INited=new WeakSet();
  // debounce semplice
  function debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this,
                args = arguments;
            clearTimeout(t);
            t = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait || 120);
        };
  }

  function isCcReady(){ return !!($.fn && $.fn.ccSelect2); }

  // --- init singolo
  function enhanceSelect(el){
    if (!($.fn && $.fn.ccSelect2)) { LOG('skip (ccSelect2 not ready) →', el); return; }
    if (!el) { LOG('skip (el falsy)'); return; }
    if (INited.has(el)) { LOG('skip (already inited) →', el.id || el); return; }
    if (el.classList.contains('select2-hidden-accessible')) { LOG('skip (già select2) →', el.id || el); return; }

    var $el = $(el);
    if ($el.is('[data-cc-s2="off"]')) { BADGE('Select2 Compat', 'skip forzato data-cc-s2="off" → '+(el.id||''), CC_COLORS.warn); return; }

    INited.add(el);

    var ph = $el.attr('data-placeholder');
    if (!ph) {
      var $first = $el.find('option[value=""], option:not([value])').first();
      if ($first.length) ph = $first.text().trim();
    }

    var opts = {
      width:'100%', placeholder: ph||'', allowClear: !!ph,
      language: {
        errorLoading: function(){ return 'Impossibile caricare i risultati.'; },
        inputTooLong: function(a){ var n=a.input.length-a.maximum; return 'Cancella '+n+' caratter'+(n>1?'i':'e'); },
        inputTooShort: function(a){ var n=a.minimum-a.input.length; return 'Inserisci ancora '+n+' caratter'+(n>1?'i':'e'); },
        loadingMore: function(){ return 'Carico altri risultati…'; },
        maximumSelected: function(a){ return 'Puoi selezionare al massimo '+a.maximum+' elementi'; },
        noResults: function(){ return 'Nessun risultato'; },
        searching: function(){ return 'Ricerca…'; },
        removeAllItems: function(){ return 'Rimuovi tutti gli elementi'; }
      },
      closeOnSelect: !$el.prop('multiple')
    };

    LOG('init →', el.id || el, { multiple: $el.prop('multiple'), placeholder: opts.placeholder });

    try {
      $el.ccSelect2(opts);
      BADGE('Select2 Compat', 'inited → '+(el.id || '<no-id>'), CC_COLORS.info);
    } catch(e){
      BADGE('Select2 Compat', 'init FAIL → '+(el.id || '<no-id>'), CC_COLORS.err);
      console && console.warn && console.warn('[CC-S2] init fail', e);
    }

    $el.on('select2:open', function(){
      var f = document.querySelector('.select2-container--open .select2-search__field');
      if (f) f.focus({ preventScroll:true });
    });

    var form = $el.closest('form').get(0);
    if (form && !form.__ccS2ResetAttached){
      form.__ccS2ResetAttached = true;
      form.addEventListener('reset', function(){
        $(form).find('select').each(function(){
          if (this.classList.contains('select2-hidden-accessible')) $(this).val(null).trigger('change');
        });
      });
    }
  }

  // --- scan (con auto-mark e skip già inizializzati)
  function scan(root){
    var $root = $(root || document);
    var c1=0, c2=0, c3=0, c4=0, c5=0;

    // 1) Elementor (ID classico): form-field-cc_s2_
    $root.find('form.elementor-form select[id^="form-field-cc_s2_"]:not(.select2-hidden-accessible)')
      .not('[data-cc-s2="off"]')
      .each(function(){ if(!this.hasAttribute('data-cc-s2')) this.setAttribute('data-cc-s2','on'); c1++; enhanceSelect(this); });

    // 1b) Elementor (frontend "furbo"): match su NAME ⇒ form_fields[cc_s2_*]
    $root.find('form.elementor-form .elementor-field-type-select select[name^="form_fields[cc_s2_"]:not(.select2-hidden-accessible)')
      .not('[data-cc-s2="off"]')
      .each(function(){ if(!this.hasAttribute('data-cc-s2')) this.setAttribute('data-cc-s2','on'); c5++; enhanceSelect(this); });

    // 2) Espliciti (data/class)
    $root.find('select[data-cc-s2]:not([data-cc-s2="off"]):not(.select2-hidden-accessible), select.cc-s2:not(.select2-hidden-accessible)')
      .each(function(){ c2++; enhanceSelect(this); });

    // 3) Fuori Elementor: id prefix cc_s2_
    $root.find('select[id^="cc_s2_"]:not(.select2-hidden-accessible)')
      .not('[data-cc-s2="off"]')
      .each(function(){ if(!this.hasAttribute('data-cc-s2')) this.setAttribute('data-cc-s2','on'); c3++; enhanceSelect(this); });

    // 4) Registry custom
    if (window.CC_S2 && Array.isArray(window.CC_S2.__registry__)) {
      window.CC_S2.__registry__.forEach(function(r){
        $root.find(r.selector).not('[data-cc-s2="off"]').not('.select2-hidden-accessible')
          .each(function(){ c4++; enhanceSelect(this); });
      });
    }

    BADGE('Select2 Compat', 'scan done | id:'+c1+' name:'+c5+' explicit:'+c2+' off-merdor:'+c3+' registry:'+c4, CC_COLORS.info);
  }

  var debouncedScan = debounce(scan, DNC_TMOUT || 200);

    // ---------- boot con retry (come EPV) ----------
    function bootWithRetry(root) {
        var tries = 0;
        (function loop() {
            debouncedScan(root);
            if (isCcReady() || tries++ > READY_CHECK_MAX) return;
            setTimeout(loop, READY_CHECK_WAIT);
        })();
    }

  // API
  window.CC_S2 = window.CC_S2 || {};
  window.CC_S2.__registry__ = window.CC_S2.__registry__ || [];
  window.CC_S2.register = function(selector, opts){ window.CC_S2.__registry__.push({selector:selector, opts:opts||{}}); };
  window.CC_S2.init  = function(root){ bootWithRetry(root||document); };
  window.CC_S2.scan  = function(root){ debouncedScan(root||document); };

  // Hooks
  $(function(){
    BADGE('Select2 Compat', 'DOM ready → init', CC_COLORS.gold);
    window.CC_S2.init(document);
  });

  // Elementor Popup: quando apre, scansiona il contenuto del popup
  $(document).on('elementor/popup/show', function(e, id, instance){
    var root = instance && instance.$element && instance.$element[0];
    BADGE('Select2 Compat', 'popup/show → scan', CC_COLORS.gold);
    window.CC_S2.scan(root || document);
  });

  // A volte i widget vengono “pronti” dopo init: piccolo microtask
  $(window).on('elementor/frontend/init', function(){
    if (window.elementorFrontend && elementorFrontend.hooks) {
      elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope){
        // micro-delay per attendere il rendering interno
        setTimeout(function(){ window.CC_S2.scan($scope && $scope[0]); }, 10);
      });
    }
  });

  $(window).on('elementor/editor/init', function(){
    BADGE('Select2 Compat', 'editor/init', CC_COLORS.gold);
    window.CC_S2.init(document);
    var iframe = document.querySelector('iframe[name="elementor-preview-iframe"], #elementor-preview-iframe');
    if (!iframe) return;
    var hook = function(){
      var d = iframe.contentDocument;
      if (!d) return;
      BADGE('Select2 Compat', 'iframe ready → init+observe', CC_COLORS.gold);
      window.CC_S2.init(d);
      var mo = new MutationObserver(function(){ scan(d); });
      mo.observe(d.documentElement, { childList:true, subtree:true });
    };
    if (iframe.contentDocument && iframe.contentDocument.readyState!=='loading') hook();
    iframe.addEventListener('load', hook);
  });

  $(document).on('ajaxComplete', function(){
    BADGE('Select2 Compat', 'ajaxComplete → scan', CC_COLORS.gold);
    scan(document);
  });

  // MutationObserver sul body (solo frontend) per nuovi select caricati via ajax/JS
  (function(){
    try{
      var tick = null;
      var mo = new MutationObserver(function(){
        if (tick) return;
        tick = setTimeout(function(){
          tick = null;
          BADGE('Select2 Compat', 'MO body → scan', CC_COLORS.gold);
          if (window.CC_S2 && window.CC_S2.scan) window.CC_S2.scan(document);
        }, 120);
      });
      mo.observe(document.body, {childList:true, subtree:true});
    }catch(e){}
  })();

  window.addEventListener('cc:s2:scan', function(e){
    BADGE('Select2 Compat', 'cc:s2:scan (manual) → scan', CC_COLORS.gold);
    scan((e && e.detail && e.detail.root) || document);
  });
})(jQuery);
JS;
  wp_add_inline_script('cc-s2-init', $init);

  // enqueue in ordine
  wp_enqueue_script('cc-s2-pre');
  wp_enqueue_script('cc-s2-vendor');
  wp_enqueue_script('cc-s2-post');
  wp_enqueue_script('cc-s2-init');
}

/**
 * ============================================================
 * 🧩 Debug / Dev Notes
 * ============================================================
 *
 *  Per attivare la modalità debug e visualizzare:
 *   - Banner console con versione Select2 rilevata
 *   - Pillola DOM “CC Select2” temporanea in basso a destra
 *
 *  👉 Aggiungi una delle seguenti opzioni:
 *
 *  In wp-config.php:
 *      define('CC_S2_DEBUG', true);
 *
 *  Oppure tramite filtro:
 *      add_filter('cc_s2_debug', '__return_true');
 *
 *  Questo non altera il comportamento del plugin,
 *  ma espone informazioni utili per debugging e version check.
 *
 *  @since 1.1.0
 *  @author CodeCorn™
 */
add_filter('cc_s2_debug', '__return_true');

/** @since 1.1.2 */
// ### Come usarla al volo

// * Per inizializzare **solo** un’area appena montata:

// ```js
// window.CC_S2.scan(scopeElement);   // debounced
// // o forzare subito con retry:
// window.CC_S2.init(scopeElement);
// ```

// * Per registrare selettori custom:

// ```js
// CC_S2.register('form.my-form select.enhance-me');
// CC_S2.scan(); // o aspetta i trigger automatici
// ```