# 🧩 CodeCorn™ MU – Select2 Compat

> WordPress MU-plugin providing isolated Select2 integration (4.0.13) for Elementor forms.  
> © CodeCorn™ Technology — MIT License

# **📄 CHANGELOG.md**

---

## [1.1.71] – 2025-10-20

### Added

-   **Strict passive shim** applied globally to `wheel`, `touchstart`, and `touchmove` events.  
    → Eliminates Chrome “scroll-blocking” warnings across all contexts.
-   **Meta exposure** to `window`:
    ```js
    window.CC_S2.version; // plugin version (e.g. "1.1.71")
    window.CC_S2.vendor; // vendor version ("4.0.13")
    window.CC_S2_VERSION; // readonly alias
    window.CC_S2_VENDOR;
    ```