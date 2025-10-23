# 🧩 CodeCorn™ MU – Select2 Compat `v1.1.71`


**Version:** 1.1.71  
**Author:** [CodeCorn™ Technology](https://github.com/CodeCornTech)  
**License:** MIT  
**Repository:** [github.com/CodeCornTech/mu-cc-select2-compat](https://github.com/CodeCornTech/mu-cc-select2-compat)

---

## 🔍 Description

Ultra-minimal **MU-plugin** that loads a _vendor-scoped_ **Select2** (4.0.13) for WordPress + Elementor.  
Provides a **no-conflict instance** (`$.fn.ccSelect2`) while preserving any global `$.fn.select2`.

Includes:

-   dark-mode aware styling,
-   i18n auto-loader (`assets/js/i18n/*.js`),
-   anti-flicker instant init (above-the-fold + lazy observer),
-   passive wheel shim to silence Chrome performance warnings.

---

## 📦 Installation

Place files as follows:

```

mu-plugins/
├─ mu-cc-select2-compat.php          # bootstrap MU in root
└─ codecorn/
   └─ select2-compat/
      │
      ├─ assets/
      │  ├─ css/critical.css
      │  └─ js/
      │     ├─ first.js
      │     ├─ pre.js
      │     └─ init.js
      └─ vendors/
         └─ select2/
            ├─ css/select2.min.css
            └─ js/
               ├─ select2.full.min.js
               ├─ select2.min.js
               └─ i18n/*.js    # <-- QUI i bundle lingue

```

> MU-plugins are auto-loaded. No activation required.

---

## 🚀 Features

-   Loads **Select2 4.0.13** locally, without CDN.
-   Exposes isolated instance as `$.fn.ccSelect2`.
-   Keeps global `$.fn.select2` intact (restored after boot).
-   Auto-enhances Elementor Form `<select>` (frontend + editor iframe).
-   Handles modals (Elementor / Bootstrap) via smart `dropdownParent`.
-   Dark-mode adaptive styles (`.scheme_dark` + `prefers-color-scheme`).
-   Localized via auto-detected locale → dynamic i18n file load.
-   Adds **passive wheel listener** to remove Chrome “scroll-blocking” warnings.
-   Instant ATF init + IntersectionObserver + MutationObserver fallback.

---

## ⚙️ Internal Load Order

```

[first.js] → [select2.full.min.js] → [pre.js] → [init.js]

```

Ensures the passive-wheel shim runs **before** the vendor library and Select2 is ready before Elementor hooks fire.

---

## 🧰 Usage

Default behaviour: automatically enhances all Elementor form selects and any `<select data-cc-s2>`.

```html
<select id="cc_s2_region" data-cc-s2 data-placeholder="Seleziona una regione">
    <option value="">Scegli...</option>
    <option value="LAZ">Lazio</option>
    <option value="TOS">Toscana</option>
</select>
```

---

### 🧩 Manual registration

```js
window.CC_S2 &&
    window.CC_S2.register('#cc_s2_speciale', {
        placeholder: 'Tipo di intervento',
        allowClear: true,
        minimumResultsForSearch: 3,
    });
window.CC_S2 && window.CC_S2.scan(document);
```

---

### 🧩 Global callbacks

Hook into init phases for custom UI logic:

```js
window.CC_S2.onInit = ($el, opts) => console.log('Init:', $el.attr('id'));
window.CC_S2.afterInit = ($el) => {
    $el.next('.select2').find('.select2-selection').css('border-color', '#c1a269');
};
```

---

### 🧩 AJAX / Modals re-init

```js
document.addEventListener('ajaxComplete', () => window.CC_S2.scan(document));
jQuery(document).on('elementor/popup/show', (_, __, inst) => window.CC_S2.scan(inst?.$element?.[0] || document));
```

---

## 🌍 Internationalization

Languages live in `vendors/select2/js/i18n/*.js`.
The plugin automatically picks the closest match to `get_locale()` (e.g. `pt-BR` → `pt` → `en`).

### Override examples

```php
// Force specific language
define('CC_S2_LANG', 'fr');

// Or filter dynamically
add_filter('cc_s2_i18n_locale', fn() => 'de_DE');
```

---

## 🧠 Debug Mode

Enable rich console banners and badges:

```php
define('CC_S2_DEBUG', true);
```

or via filter:

```php
add_filter('cc_s2_debug', '__return_true');
```

Shows version info and runtime logs (`[CC-S2] init → elementor-form-field`).

---

## 🎨 Dark Mode

Handled via both:

-   `@media (prefers-color-scheme: dark)`
-   `.scheme_dark` class (ThemeREX / Elementor).

Custom styles live in `assets/css/critical.css`.

---

## 🧩 Meta exposed to JS

Accessible via `window.CC_S2_META`:

```js
{
  plugin:  "CodeCorn™ Select2 Compat",
  version: "1.1.71",
  vendor:  "4.0.13"
}
```

---

## 🧱 Folder summary

| Path                      | Purpose                            |
| ------------------------- | ---------------------------------- |
| `assets/js/first.js`      | Passive wheel shim (before vendor) |
| `assets/js/pre.js`        | Bridge (pre+post boot)             |
| `assets/js/init.js`       | Lazy init + i18n + observers       |
| `assets/css/critical.css` | Base + dark mode CSS               |
| `vendors/select2`         | Vendor-scoped Select2              |
| `vendors/select2/js/i18n` | Language bundles                   |

---

### 🧰 Load Order

```
[first.js] → [select2.full.min.js] → [pre.js] → [init.js]
```

## Ensures the passive shim precedes vendor load and `$.fn.ccSelect2` is ready for Elementor hooks.

### 📝 License

MIT — © CodeCorn™ Technology

---

### ❤️ Maintainer

**Federico Girolami** — [@fgirolami29](https://github.com/fgirolami29)
Maintained under **CodeCorn™ Technology**

---
