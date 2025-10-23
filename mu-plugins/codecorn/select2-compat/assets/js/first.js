/*! CodeCorn™ Select2 Compat — FIRST (BEFORE VENDOR) */
/*! Passive shim: wheel + touchstart + touchmove (STRICT) */
// @ts-nocheck
(function () {
  var ET = window.EventTarget && window.EventTarget.prototype;
  if (!ET || !ET.addEventListener) return;
  if (ET.__ccS2PassiveShimApplied) return;
  ET.__ccS2PassiveShimApplied = true;

  // ✅ Modalità: STRICT = forza passive:true sempre (warning zero)
  //    SAFE   = imposta passive:true solo se non specificato (warning quasi-zero, zero rotture)
  var FORCE_STRICT = true; // <— cambia a false se mai servisse (1 riga)

  var orig = ET.addEventListener;
  var PASSIVE_TYPES = { wheel: 1, touchstart: 1, touchmove: 1 };

  ET.addEventListener = function (type, listener, options) {
    if (PASSIVE_TYPES[type]) {
      if (FORCE_STRICT) {
        // forza SEMPRE passive:true, qualunque cosa chieda il chiamante
        if (options == null) {
          options = { passive: true };
        } else if (typeof options === 'boolean') {
          options = { capture: !!options, passive: true };
        } else if (typeof options === 'object') {
          options = Object.assign({}, options, { passive: true });
        }
      } else {
        // SAFE: setta passive:true solo se non specificato
        if (options == null) {
          options = { passive: true };
        } else if (typeof options === 'boolean') {
          options = { capture: !!options, passive: true };
        } else if (typeof options === 'object' && !('passive' in options)) {
          options = Object.assign({}, options, { passive: true });
        }
      }
    }
    return orig.call(this, type, listener, options);
  };
})();
