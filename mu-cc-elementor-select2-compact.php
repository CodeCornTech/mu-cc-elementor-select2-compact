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
 *  Version:      1.0.0
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
  // CSS (usa quello vendorizzato, poi un piccolo tweak)
  $css_url = cc_s2_url(cc_s2_rel_base() . '/css/select2.min.css');
  wp_enqueue_style('cc-s2-css', $css_url, [], '4.0.13'); // la tua versione vendorizzata
  $tweak = <<<CSS
/* scope "soft": piena larghezza e compat con Elementor */
.cc-s2 .select2-container{ width:100% !important; }
.cc-s2 .select2-selection--single .select2-selection__rendered{ line-height: 38px; }
.cc-s2 .select2-selection--single{ height: 40px; }
.cc-s2 .select2-selection--multiple{ min-height: 40px; }
.cc-s2 .select2-selection__choice{ margin-top: 6px; }
CSS;
  // debug toggle: define('CC_S2_DEBUG', true) in wp-config.php OR add_filter('cc_s2_debug','__return_true');
  $cc_s2_debug = apply_filters('cc_s2_debug', defined('CC_S2_DEBUG') ? CC_S2_DEBUG : false);
  if ($cc_s2_debug) {
    $badgeCss = <<<CSS
      [data-cc-s2-pill]{ /* se lo preferisci visibile fisso, usa attributo e non auto-hide */
        position:fixed; right:10px; bottom:10px; z-index:2147483647;
        font:12px/1.4 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
        background:linear-gradient(135deg,#111,#333); color:#fff; padding:6px 10px;
        border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.25); letter-spacing:.2px;
      }
    CSS;
    wp_add_inline_style('cc-s2-css', $badgeCss);
  }
  wp_localize_script('cc-s2-post', 'CC_S2_OPTS', ['debug' => (bool) $cc_s2_debug]);


  wp_add_inline_style('cc-s2-css', $tweak);
  // JS sandwich anti-collisione
  wp_register_script('cc-s2-pre', false, ['jquery'], '1.0.0', true);
  wp_register_script('cc-s2-vendor', cc_s2_url(cc_s2_rel_base() . '/js/select2.full.min.js'), ['jquery', 'cc-s2-pre'], '4.0.13', true);
  wp_register_script('cc-s2-post', false, ['jquery', 'cc-s2-vendor'], '1.0.0', true);


  // PRE: salva l’eventuale select2 legacy già esistente
  $pre = <<<JS
(function($){
  // backup EVENTUALE plugin già registrato
  if (!$.fn) return;
  window._ccSelect2Legacy = $.fn.select2 ? $.fn.select2 : null;
})(jQuery);
JS;
  wp_add_inline_script('cc-s2-pre', $pre);

  // POST: cattura il plugin caricato dal vendor e rinomina in $.fn.ccSelect2, poi ripristina l’eventuale legacy
  $post = <<<'JS'
(function($){
  if (!$.fn) return;

  // 1) cattura il plugin appena caricato dal vendor
  var newPlugin = $.fn.select2 || null;

  // 2) ripristina l'eventuale legacy sul namespace ufficiale
  if (window._ccSelect2Legacy) {
    $.fn.select2 = window._ccSelect2Legacy;
  } else {
    try { delete $.fn.select2; } catch(e) { $.fn.select2 = undefined; }
  }

  // 3) esponi la nostra istanza isolata
  $.fn.ccSelect2 = newPlugin;
  window.CC_S2_READY = !!$.fn.ccSelect2;

  // 4) heuristics per versione/family (Select2 non espone una version API stabile su 4.0.x)
  function detectVersion(){
    var v = null, fam = '4.0.13', full = false;
    try {
      // vendor full vs lite
      full = !!($.fn.ccSelect2 && $.fn.ccSelect2.amd && $.fn.ccSelect2.defaults && $.fn.ccSelect2.defaults.set);

      // prova a leggere via AMD il core (alcune build espongono .version)
      if ($.fn.ccSelect2 && $.fn.ccSelect2.amd) {
        var req = $.fn.ccSelect2.amd.require;
        if (typeof req === 'function') {
          try {
            var Core = req('select2/core');
            if (Core && Core.prototype) {
              v = Core.prototype.version || Core.prototype._version || null;
            }
          } catch(e){}
        }
      }
      // fallback feature-based
      if (!v) {
        v = (full ? '4.0+ (full)' : '4.0+ (lite)');
      }
    } catch(e){}
    return {version:v, family:fam, full:full};
  }

  var info = detectVersion();
  window.CC_S2_INFO = info;

  // 5) console banner (solo se debug abilitato via PHP)
  var _dbg = !!(window.CC_S2_OPTS && window.CC_S2_OPTS.debug);
  if (_dbg) {
    var msg = [
      '%c CodeCorn™ Select2 Compat %c ' + (info.version || info.family) + ' %c ' + (info.full ? 'FULL' : 'LITE') + ' ',
      'background:#111;color:#fff;padding:3px 6px;border-radius:4px 0 0 4px;',
      'background:#222;color:#9fe870;padding:3px 6px;',
      'background:#333;color:#fff;padding:3px 6px;border-radius:0 4px 4px 0;'
    ];
    try { console.log.apply(console, msg); } catch(e){ console.log('CodeCorn Select2 Compat', info); }

    // 6) pillola DOM opzionale in basso a destra
    try {
      var pill = document.createElement('div');
      pill.textContent = 'CC Select2: ' + (info.version || info.family) + (info.full? ' • full':'');
      pill.style.cssText = 'position:fixed;z-index:2147483647;right:10px;bottom:10px;font:12px/1.4 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#111;color:#fff;padding:6px 8px;border-radius:8px;opacity:.85';
      pill.setAttribute('data-cc-s2-pill','1');
      document.documentElement.appendChild(pill);
      // auto-hide dopo 5s
      setTimeout(function(){ if (pill && pill.parentNode) pill.parentNode.removeChild(pill); }, 5000);
    } catch(e){}
  }
})(jQuery);
JS;
  wp_add_inline_script('cc-s2-post', $post);

  // INIT: integra Elementor (frontend + editor, iframe safe), single/multiple
  wp_register_script('cc-s2-init', false, ['jquery', 'cc-s2-post'], '1.0.0', true);
  $init = <<<'JS'
(function($){
  if (!$.fn) return;

  function isEditor(){
    try{
      var ef = window.elementorFrontend;
      return (ef && typeof ef.isEditMode==='function' && ef.isEditMode()) || /elementor-preview=/.test(location.search);
    }catch(e){return false;}
  }

  function enhanceSelect(el){
    var $el = $(el);
    if (!$.fn.ccSelect2) return;
    if ($el.data('cc-s2')) return;  // evita doppio init
    $el.data('cc-s2', true);

    // placeholder: data-placeholder o il testo della prima option vuota
    var ph = $el.attr('data-placeholder');
    if (!ph) {
      var $first = $el.find('option[value=""], option:not([value])').first();
      if ($first.length) ph = $first.text().trim();
    }

    var opts = {
      width: '100%',
      placeholder: ph || '',
      allowClear: !!ph,
      // lingua minima: IT
      language: {
        errorLoading: function(){ return 'Impossibile caricare i risultati.'; },
        inputTooLong: function(args){ var over = args.input.length - args.maximum; return 'Cancella ' + over + ' caratter' + (over>1?'i':'e'); },
        inputTooShort: function(args){ var n = args.minimum - args.input.length; return 'Inserisci ancora ' + n + ' caratter' + (n>1?'i':'e'); },
        loadingMore: function(){ return 'Carico altri risultati…'; },
        maximumSelected: function(args){ return 'Puoi selezionare al massimo ' + args.maximum + ' elementi'; },
        noResults: function(){ return 'Nessun risultato'; },
        searching: function(){ return 'Ricerca…'; },
        removeAllItems: function(){ return 'Rimuovi tutti gli elementi'; }
      },
      closeOnSelect: !$el.prop('multiple')
    };

    // init isolato
    try { $el.ccSelect2(opts); } catch(e){ console && console.warn && console.warn('[CC-S2] init fail', e); }

    // fix focus/keyboard su Elementor
    $el.on('select2:open', function(){
      var $sf = document.querySelector('.select2-container--open .select2-search__field');
      if ($sf) { $sf.focus({preventScroll:true}); }
    });

    // reset on form reset
    var form = $el.closest('form').get(0);
    if (form && !form.__ccS2ResetAttached){
      form.__ccS2ResetAttached = true;
      form.addEventListener('reset', function(){
        // svuota selezione ma non distrugge l'istanza
        $(form).find('select').each(function(){
          var $s = $(this);
          if ($s.data('cc-s2')) {
            $s.val(null).trigger('change');
          }
        });
      });
    }
  }

  function enhanceScope(root){
    var $root = $(root || document);
    // Elementor: i select standard del widget form
    $root.find('form.elementor-form select').each(function(){
      enhanceSelect(this);
    });
  }

  // Frontend pronto
  $(function(){ enhanceScope(document); });

  // Elementor Frontend
  $(window).on('elementor/frontend/init', function(){
    if (window.elementorFrontend && elementorFrontend.hooks) {
      elementorFrontend.hooks.addAction('frontend/element_ready/form.default', function($scope){
        enhanceScope($scope[0]);
      });
    }
  });

  // Editor: osserva anche l'iframe di preview
  $(window).on('elementor/editor/init', function(){
    // parent
    enhanceScope(document);
    // iframe
    var iframe = document.querySelector('iframe[name="elementor-preview-iframe"], #elementor-preview-iframe');
    if (iframe && iframe.contentWindow) {
      var onLoad = function(){
        var d = iframe.contentDocument;
        enhanceScope(d);
        // observe mutazioni per nuovi widget
        var mo = new MutationObserver(function(muts){
          for (var i=0;i<muts.length;i++){
            if (muts[i].addedNodes && muts[i].addedNodes.length){
              enhanceScope(d);
              break;
            }
          }
        });
        mo.observe(d.documentElement, {childList:true, subtree:true});
      };
      if (iframe.contentDocument && iframe.contentDocument.readyState !== 'loading') onLoad();
      iframe.addEventListener('load', onLoad);
    }
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
 *  @since 1.0.0
 *  @author CodeCorn™
 */
