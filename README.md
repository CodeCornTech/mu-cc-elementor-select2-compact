# 🧩 CodeCorn™ MU - Elementor Select2 Compact

**Version:** 1.0.0  
**Author:** [CodeCorn™ Technology](https://github.com/CodeCornTech)  
**License:** MIT

---

## 🔍 Description

Minimal **MU-plugin** that loads a vendor-scoped **Select2** without colliding with existing instances (e.g. 4.0.3) and auto-enhances Elementor Form `<select>` (single & multiple).  
Our build exposes Select2 under `$.fn.ccSelect2`, preserving any global `$.fn.select2` already registered by other plugins/themes.

---

## 📦 Installation

Copy the files into your WordPress install exactly as follows:

```

wp-content/
└── mu-plugins/
├── cc-select2-compat.php
└── codecorn/
└── vendors/
└── select2/
├── css/
│   └── select2.min.css
└── js/
├── select2.full.min.js
└── select2.min.js   (optional, unused)

```

> MU-plugins are auto-loaded by WordPress. No activation needed.

---

## 🚀 What it does

-   Loads **vendor Select2** (local, no CDN).
-   Isolates it as `$.fn.ccSelect2` (no conflicts).
-   Enhances Elementor form selects (frontend + editor iframe).
-   IT localization, placeholder auto-detection, reset on `form.reset`.

---

## 🧰 Usage

Works out-of-the-box.  
If you want to limit enhancement to specific fields, add a class and filter the selector in the init block (see inline comments inside `cc-select2-compat.php`).

---

## 🧠 Come si attiva il Debug

Per mostrare il banner in console e la pillola “CC Select2” di debug:

**In `wp-config.php`:**

```php
define('CC_S2_DEBUG', true);
```

**Oppure tramite filtro (es. functions.php o MU-plugin):**

```php
add_filter('cc_s2_debug', '__return_true');
```

> Mostra versione/famiglia Select2 caricata e conferma che l’istanza isolata `$.fn.ccSelect2` sia correttamente attiva.

---

## 📝 License

MIT — © CodeCorn™ Technology
