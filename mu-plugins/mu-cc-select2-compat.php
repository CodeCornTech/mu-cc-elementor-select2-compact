<?php
/**
 *  ╔════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╗
 *  ║  CodeCorn™ Select2 Compat (MU Plugin)                                                                              ║
 *  ║  Vendor-scoped Select2 for WordPress + Elementor (no conflicts).                                                   ║
 *  ║  Exposes $.fn.ccSelect2 and preserves any existing $.fn.select2 .                                                  ║
 *  ║  Includes instant init, anti-flicker preload, IntersectionObserver lazy-init and AJAX / popup hooks.               ║
 *  ╚════════════════════════════════════════════════════════════════════════════════════════════════════════════════════╝
 * 
 *  Plugin Name:  CodeCorn™ Select2 Compat
 *  Plugin URI:   https://github.com/CodeCornTech/cc-elementor-form-select2
 *  Description:  Select2 vendorizzata no-conflict per WordPress + Elementor . Init istantaneo , anti-flicker , hook popup / AJAX .
 *  Version:      1.1.70
 *  Author:       CodeCorn™ Technology
 *  Author URI:   https://github.com/fgirolami29
 *  License:      MIT
 *  License URI:  https://opensource.org/licenses/MIT
 *  Requires PHP: 7.4
 *  Requires at least: 5.8
 *  Tested up to: 6.7
 *  Text Domain:  codecorn-select2
 *  Domain Path:  /languages
 * 
 *  @package CodeCorn\Select2Compat
 *  @since   1.1.70
 */

if (!defined('ABSPATH'))
    exit;

final class CC_S2
{
    public const VER = '1.1.70';
    public const VENDOR_VER = '4.0.13';

    private const H_CSS = 'cc-s2-css';
    private const H_FIRST = 'cc-s2-first';
    private const H_CRITCSS = 'cc-s2-critical';
    private const H_VEND = 'cc-s2-vendor';
    private const H_BRIDGE = 'cc-s2-bridge';
    private const H_INIT = 'cc-s2-init';

    public static function boot(): void
    {
        add_action('plugins_loaded', [__CLASS__, 'hooks'], 0);
    }

    public static function hooks(): void
    {
        // Pre-paint : classe anti-flicker + preload asset
        add_action('wp_head', [__CLASS__, 'head_critical'], 0);

        // Enqueue in HEAD con priorità alta
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue'], 5);
        add_action('elementor/editor/after_enqueue_scripts', [__CLASS__, 'enqueue'], 5);

        // Debug facoltativo ( rimuovi in produzione se vuoi )
        //add_filter('cc_s2_debug', '__return_false');
        add_filter('cc_s2_debug', '__return_true');
    }

    private static function base(): string
    {
        return 'mu-plugins/codecorn/select2-compat';
    }
    private static function url(string $rel): string
    {
        return content_url(ltrim(self::base() . '/' . ltrim($rel, '/'), '/'));
    }

    /**
     * Inserisce al volo :
     *  - Classe html.cc-s2-booting prima del primo paint
     *  - Preload per CSS / JS vendor
     *  - Un micro critical CSS ( opzionale , puoi toglierlo se usi il file critical.css )
     */
    public static function head_critical(): void
    {
        // Flag CSS: aggiunge la classe cc-s2-booting al <html> senza script inline sporco
        add_filter('language_attributes', function ($output) {
            return "{$output} class=\"cc-s2-booting\"";
        });

        // Preload vendor CSS e JS con wp_resource_hints (nativo)
        add_filter('wp_resource_hints', function ($urls, $relation_type) {
            if ($relation_type === 'preload') {
                $urls[] = [
                    'href' => self::url('vendors/select2/css/select2.min.css'),
                    'as' => 'style',
                    'crossorigin' => true,
                ];
                $urls[] = [
                    'href' => self::url('vendors/select2/js/select2.full.min.js'),
                    'as' => 'script',
                    'crossorigin' => true,
                ];
            }
            return $urls;
        }, 10, 2);
    }

    // i18n utils LOADER // i18n utils LOADER
    // i18n utils LOADER // i18n utils LOADER

    /** Path assoluto alla cartella i18n */
    private static function i18n_dir(): string
    {
        return WP_CONTENT_DIR . '/mu-plugins/codecorn/select2-compat/vendors/select2/js/i18n';
    }

    /** URL base (termina con /) alla cartella i18n */
    private static function i18n_base_url(): string
    {
        return self::url('vendors/select2/js/i18n/'); // produce .../vendors/js/i18n/
    }

    /** Elenca i pacchetti disponibili (['it','en','pt-BR',...]) con filtro. */
    private static function i18n_packs(): array
    {
        $files = glob(self::i18n_dir() . '/*.js') ?: [];
        $packs = array_map(static fn($f) => basename($f, '.js'), $files);
        // Permetti override/integrazione via filtro
        return array_values(array_unique((array) apply_filters('cc_s2_i18n_packs', $packs)));
    }

    /** Normalizza locale WP → es. "pt_BR"→"pt-BR", "it_IT"→"it-IT", "it"→"it" */
    private static function i18n_normalize(string $loc): string
    {
        $loc = str_replace('_', '-', trim($loc));
        if (strpos($loc, '-') !== false) {
            [$lang, $region] = array_pad(explode('-', $loc, 2), 2, '');
            $lang = strtolower($lang);
            $region = strtoupper($region);
            return $region ? "{$lang}-{$region}" : $lang;
        }
        return strtolower($loc);
    }

    /**
     * Sceglie la lingua migliore: exact → solo lingua → 'en'.
     * Accetta override via filtro 'cc_s2_i18n_locale' e costante CC_S2_LANG.
     */
    private static function i18n_pick_langNODEBUG(?string $locale = null): string
    {
        // priorità: costante → parametro → get_locale()
        if (defined('CC_S2_LANG') && CC_S2_LANG) {
            $locale = (string) CC_S2_LANG;
        }
        $locale = $locale ?: get_locale();

        $norm = self::i18n_normalize((string) apply_filters('cc_s2_i18n_locale', $locale));
        $packs = self::i18n_packs();

        $candidates = [$norm, strtolower(substr($norm, 0, 2)), 'en'];
        foreach ($candidates as $c) {
            if (in_array($c, $packs, true))
                return $c;
        }
        return 'en';
    }
    /**
     * Sceglie la lingua migliore: exact → solo lingua → 'en'.
     * Accetta override via filtro 'cc_s2_i18n_locale' e costante CC_S2_LANG.
     */
    private static function i18n_pick_lang(?string $locale = null): string
    {
        // priorità: costante → parametro → get_locale()
        $src = 'get_locale()';
        if (defined('CC_S2_LANG') && CC_S2_LANG) {
            $locale = (string) CC_S2_LANG;
            $src = 'CC_S2_LANG';
        }
        $locale = $locale ?: get_locale();
        $norm = self::i18n_normalize((string) apply_filters('cc_s2_i18n_locale', $locale));
        $packs = self::i18n_packs();
        $candidates = [$norm, strtolower(substr($norm, 0, 2)), 'en'];
        foreach ($candidates as $c) {
            if (in_array($c, $packs, true)) {
                return $c;
            }
        }
        return 'en';
    }

    public static function enqueue(): void
    {
        $debug = (bool) apply_filters('cc_s2_debug', defined('CC_S2_DEBUG') ? CC_S2_DEBUG : false);

        // 1 ) Vendor CSS
        wp_enqueue_style(
            self::H_CSS,
            self::url('vendors/select2/css/select2.min.css'),
            [],
            self::VENDOR_VER
        );

        // 2 ) Critical CSS ( file + qualche regola inline minima )
        wp_enqueue_style(
            self::H_CRITCSS,
            self::url('assets/css/critical.css'),
            [self::H_CSS],
            self::VER
        );

        // --- JS IN HEAD: FIRST -> VENDOR -> BRIDGE -> INIT ---
        // FIRST: lo shim (non richiede jQuery ma lo mettiamo dopo jQuery per ordine stabile)
        wp_register_script(
            self::H_FIRST,
            self::url('assets/js/first.js'),
            ['jquery'],
            self::VER,
            false // HEAD
        );

        // 3 ) JS in HEAD : vendor → bridge → init
        wp_register_script(
            self::H_VEND,
            self::url('vendors/select2/js/select2.full.min.js'),
            ['jquery', self::H_FIRST],
            self::VENDOR_VER,
            false
        );

        // BRIDGE dopo VENDOR
        wp_register_script(
            self::H_BRIDGE,
            self::url('assets/js/pre.js'),     // useremo pre.js come “bridge” ( pre + post )
            ['jquery', self::H_VEND],
            self::VER,
            false
        );

        // INIT dopo BRIDGE
        wp_register_script(
            self::H_INIT,
            self::url('assets/js/init.js'),
            ['jquery', self::H_BRIDGE],
            self::VER,
            false
        );

        // Localize (bridge + init)
        wp_localize_script(self::H_BRIDGE, 'CC_S2_OPTS', ['debug' => $debug]);
        wp_localize_script(self::H_BRIDGE, 'CC_S2_META', [
            'plugin' => 'CodeCorn™ Select2 Compat',
            'version' => self::VER,
            'vendor' => self::VENDOR_VER,
        ]);

        // Se vuoi, esponi anche i pacchetti per debug: // 'packs' => self::i18n_packs(),
        $default_lang = self::i18n_pick_lang();
        wp_localize_script(
            self::H_INIT,
            'CC_S2_I18N',
            [
                'base' => self::i18n_base_url(), // deve terminare con /
                'default' => $default_lang,
            ]
        );
        // Enqueue
        wp_enqueue_script(self::H_FIRST);
        wp_enqueue_script(self::H_VEND);
        wp_enqueue_script(self::H_BRIDGE);
        wp_enqueue_script(self::H_INIT);
    }
}

CC_S2::boot();
