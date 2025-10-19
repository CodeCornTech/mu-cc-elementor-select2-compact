/*! CodeCorn™ Select2 Compat — FIRST (BEFORE VENDOR) */
/*! Passive 'wheel' shim — MUST run BEFORE vendor */
// @ts-nocheck
(function(){
  var ET = window.EventTarget && window.EventTarget.prototype;
  if (!ET || !ET.addEventListener) return;
  if (ET.__ccS2WheelShimApplied) return; // evita doppio patch
  ET.__ccS2WheelShimApplied = true;

  var orig = ET.addEventListener;
  ET.addEventListener = function(type, listener, options){
    if (type === 'wheel') {
      var skip = document.documentElement.classList.contains('cc-no-passive-wheel');
      if (!skip) {
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
