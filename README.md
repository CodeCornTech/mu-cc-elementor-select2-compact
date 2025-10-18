# 🧩 CodeCorn™ MU - Elementor Select2 Compact

**Version:** 1.1.1
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

### 🧩 1️⃣ Estendere via `data-cc-s2-opts`

```html
<select id="cc_s2_region" data-cc-s2 data-cc-s2-opts='{"placeholder":"Scegli la regione","allowClear":true,"minimumResultsForSearch":5}'>
    <option value="">Scegli...</option>
    <option value="LAZ">Lazio</option>
    <option value="TOS">Toscana</option>
</select>
```

👉 automaticamente mergea con i `opts` base dentro `enhanceSelect()`
(quindi non serve scrivere JS)

---

### 🧩 2️⃣ Estendere via `CC_S2.register(selector, opts)`

```js
window.CC_S2 &&
    window.CC_S2.register('#cc_s2_speciale', {
        placeholder: 'Tipo di intervento',
        allowClear: true,
        minimumResultsForSearch: 2,
        dropdownAutoWidth: true,
    });

// Forza re-init se già in pagina
window.CC_S2 && window.CC_S2.init(document);
```

Perfetto per attivare select fuori dai form Elementor o caricati via AJAX.

---

### 🧩 3️⃣ Hook globale “onInit” / “afterInit”

nel caso vuoi “agganciare” callback globali (es. logging o custom UI)

```js
window.CC_S2.onInit = function ($el, opts) {
    console.log('✅ CC_S2 init on:', $el.attr('id'), opts);
};

window.CC_S2.afterInit = function ($el) {
    console.log('🎨 post-init styling:', $el.attr('id'));
    // esempio: cambia colore bordo al volo
    $el.next('.select2').find('.select2-selection').css('border-color', '#c1a269');
};
```

E dentro `enhanceSelect(el)` basterebbe aggiungere:

```js
if (window.CC_S2.onInit) window.CC_S2.onInit($el, opts);
try { $el.ccSelect2(opts); } catch(e){ ... }
if (window.CC_S2.afterInit) window.CC_S2.afterInit($el);
```

---

### 🧩 4️⃣ Full re-init per AJAX reload o modali

```js
document.addEventListener('ajaxComplete', function () {
    window.CC_S2 && window.CC_S2.init(document);
});
```

---

## 📝 License

MIT — © CodeCorn™ Technology
