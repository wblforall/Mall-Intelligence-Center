/* Bar tab event: tab aktif otomatis terlihat + offset sticky mengikuti tinggi topbar. */
(function () {
    'use strict';
    var bar = document.getElementById('evTabs');
    if (!bar) return;
    var inner = bar.querySelector('.ev-tabs-in');
    function setTop() {
        var tb = document.querySelector('.topbar');
        bar.style.setProperty('--ev-tabs-top', (tb ? tb.offsetHeight : 0) + 'px');
    }
    function reveal() {
        var a = inner && inner.querySelector('.is-active');
        if (!a || inner.scrollWidth <= inner.clientWidth) return;
        inner.scrollLeft = Math.max(0, a.offsetLeft - (inner.clientWidth - a.offsetWidth) / 2);
    }
    function fades() {
        var max = inner.scrollWidth - inner.clientWidth;
        bar.classList.toggle('can-l', inner.scrollLeft > 4);
        bar.classList.toggle('can-r', inner.scrollLeft < max - 4);
    }
    setTop(); reveal(); fades();
    window.addEventListener('resize', function () { setTop(); fades(); });
    inner.addEventListener('scroll', fades, { passive: true });
    window.addEventListener('load', function () { setTop(); reveal(); fades(); });
})();
