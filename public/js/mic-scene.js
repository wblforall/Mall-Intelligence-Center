/**
 * Adegan login MIC: ilustrasi gedung dari logo MIC (public/img/mic-building.png) yang
 * "dibangun" bagian demi bagian di atas latar malam.
 *
 * Urutan: bayangan gedung redup -> seluruh gedung naik SEKALIGUS dalam satu sapuan dari bawah
 * tanpa garis di tepinya. Setelah selesai: pantulan di air, kilau melintasi kaca, jendela
 * berpendar hangat, gedung melayang pelan, bintang berkelip, dan parallax kursor.
 * Klik adegan untuk memutar ulang. Hormati prefers-reduced-motion (langsung keadaan akhir).
 *
 * Pakai:  <svg id="mic-scene" data-building="URL-gedung.png" aria-hidden="true"></svg>
 * Koordinat bagian dalam piksel gambar gedung (1288 x 580).
 */
(function () {
  'use strict';
  var svg = document.getElementById('mic-scene');
  if (!svg) return;
  var NS = 'http://www.w3.org/2000/svg', XL = 'http://www.w3.org/1999/xlink';
  var IMG = svg.getAttribute('data-building') || '';
  var BW = 1288, BH = 580;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var VB = { x: -90, y: -170, w: 1468, h: 940 };

  /* ---------- bagian gedung (persegi pengungkapan + arah) ---------- */
  var PARTS = [
    { id: 'all', region: [-40, -40, BW + 80, BH + 90], dir: 'up', at: 0.5, dur: 2.7 }
  ];
  var GLOW_AT = 3.5, TAIL = 5.4;
  // area jendela yang berpendar (x, y, lebar, tinggi, radius)
  var WINDOWS = [
    [380, 330, 240, 120], [650, 250, 380, 60], [650, 320, 380, 60], [650, 390, 380, 60], [1060, 270, 150, 160], [250, 330, 120, 60]
  ];

  /* ---------- util ---------- */
  function el(tag, attrs, parent) {
    var e = document.createElementNS(NS, tag);
    for (var k in attrs) { if (k === 'href') e.setAttributeNS(XL, 'xlink:href', attrs[k]); else e.setAttribute(k, attrs[k]); }
    if (parent) parent.appendChild(e);
    return e;
  }
  function ease(t) { t = Math.max(0, Math.min(1, t)); return 1 - Math.pow(1 - t, 3); }
  var seed = 5;
  function rnd() { seed = (seed * 16807) % 2147483647; return (seed - 1) / 2147483646; }

  svg.setAttribute('viewBox', VB.x + ' ' + VB.y + ' ' + VB.w + ' ' + VB.h);
  svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
  svg.setAttribute('role', 'img');
  svg.setAttribute('aria-label', 'Illustration of the Mall Intelligence Center building');

  var defs = el('defs', {}, svg);
  var gBg = el('linearGradient', { id: 'micBg', x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
  el('stop', { offset: 0, 'stop-color': '#0b1636', 'stop-opacity': 0 }, gBg);
  el('stop', { offset: 1, 'stop-color': '#0b1636', 'stop-opacity': 0 }, gBg);
  var halo = el('radialGradient', { id: 'micHalo', cx: .5, cy: .5, r: .5 }, defs);
  el('stop', { offset: 0, 'stop-color': '#3b82f6', 'stop-opacity': .42 }, halo);
  el('stop', { offset: .55, 'stop-color': '#4f46e5', 'stop-opacity': .14 }, halo);
  el('stop', { offset: 1, 'stop-color': '#4f46e5', 'stop-opacity': 0 }, halo);
  var warm = el('radialGradient', { id: 'micWarm', cx: .5, cy: .5, r: .5 }, defs);
  el('stop', { offset: 0, 'stop-color': '#ffd98a', 'stop-opacity': .95 }, warm);
  el('stop', { offset: 1, 'stop-color': '#ffb347', 'stop-opacity': 0 }, warm);
  var floor = el('linearGradient', { id: 'micFloor', x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
  el('stop', { offset: 0, 'stop-color': '#fff', 'stop-opacity': .5 }, floor);
  el('stop', { offset: 1, 'stop-color': '#fff', 'stop-opacity': 0 }, floor);
  var fMask = el('mask', { id: 'micFloorMask', maskUnits: 'userSpaceOnUse', x: -100, y: BH, width: BW + 200, height: 300 }, defs);
  el('rect', { x: -100, y: BH, width: BW + 200, height: 300, fill: 'url(#micFloor)' }, fMask);
  var fNight = el('filter', { id: 'micGhost', x: 0, y: 0, width: 1, height: 1, 'color-interpolation-filters': 'sRGB' }, defs);
  el('feColorMatrix', { type: 'matrix', values: '0.16 0.16 0.16 0 0.03  0.2 0.2 0.2 0 0.06  0.34 0.34 0.34 0 0.14  0 0 0 0.55 0' }, fNight);
  var fBlur = el('filter', { id: 'micSoft', x: '-20%', y: '-20%', width: '140%', height: '140%' }, defs);
  el('feGaussianBlur', { stdDeviation: 9 }, fBlur);
  var fBlur2 = el('filter', { id: 'micBlurL', x: '-30%', y: '-30%', width: '160%', height: '160%' }, defs);
  el('feGaussianBlur', { stdDeviation: 22 }, fBlur2);

  var root = el('g', { 'class': 'mic-root' }, svg);

  /* ---------- latar: halo, bintang, siluet kota, partikel ---------- */
  var gBack = el('g', { 'class': 'mic-back' }, root);
  el('ellipse', { cx: BW / 2, cy: BH * 0.52, rx: 780, ry: 430, fill: 'url(#micHalo)' }, gBack);
  var gStars = el('g', {}, gBack);
  for (var i = 0; i < 70; i++) {
    var st = el('circle', { cx: (VB.x + rnd() * VB.w).toFixed(0), cy: (VB.y + rnd() * (VB.h * 0.62)).toFixed(0), r: (0.8 + rnd() * 1.8).toFixed(1), fill: '#cfe0ff', 'class': 'mic-star' }, gStars);
    st.style.animationDelay = (rnd() * 5).toFixed(2) + 's'; st.style.animationDuration = (2.4 + rnd() * 3.6).toFixed(2) + 's';
  }

  /* ---------- pantulan di air (di bawah gedung) ---------- */
  var gRef = el('g', { 'class': 'mic-ref', opacity: 0 }, root);
  var refm = el('g', { mask: 'url(#micFloorMask)' }, gRef);
  var refi = el('g', { transform: 'translate(0,' + (BH * 2 - 6) + ') scale(1,-1)' }, refm);
  el('image', { href: IMG, x: 0, y: 0, width: BW, height: BH, opacity: .55 }, refi);

  /* ---------- gedung: hantu redup + bagian terang ---------- */
  var gB = el('g', { 'class': 'mic-bld' }, root);
  el('image', { href: IMG, x: 0, y: 0, width: BW, height: BH, filter: 'url(#micGhost)' }, gB);

  var items = PARTS.map(function (p, i) {
    var r = p.region, id = 'micp' + i, rid = 'micr' + i;
    var mReg = el('mask', { id: rid, maskUnits: 'userSpaceOnUse', x: -60, y: -60, width: BW + 120, height: BH + 120 }, defs);
    el('rect', { x: r[0], y: r[1], width: r[2], height: r[3], fill: '#fff', filter: 'url(#micSoft)' }, mReg);
    var mAn = el('mask', { id: id, maskUnits: 'userSpaceOnUse', x: -60, y: -60, width: BW + 120, height: BH + 120 }, defs);
    var ar = el('rect', { x: r[0], y: r[1] + r[3], width: r[2], height: 0, fill: '#fff', filter: 'url(#micSoft)' }, mAn);
    var g1 = el('g', { mask: 'url(#' + rid + ')' }, gB);
    var g2 = el('g', { mask: 'url(#' + id + ')' }, g1);
    el('image', { href: IMG, x: 0, y: 0, width: BW, height: BH }, g2);
    // garis cahaya di tepi pengungkapan
    var line = el('rect', { x: 0, y: 0, width: p.dir === 'up' ? r[2] : 8, height: p.dir === 'up' ? 8 : r[3], rx: 4, fill: '#a5c8ff', opacity: 0, filter: 'url(#micSoft)' }, gB);
    return { p: p, ar: ar, line: line };
  });

  /* ---------- kilau melintasi kaca & jendela berpendar ---------- */
  var gShine = el('g', { 'class': 'mic-shine', opacity: 0 }, root);
  var sm = el('mask', { id: 'micShineMask', maskUnits: 'userSpaceOnUse', x: 0, y: 0, width: BW, height: BH, style: 'mask-type:alpha' }, defs);
  el('image', { href: IMG, x: 0, y: 0, width: BW, height: BH }, sm);
  var shGrad = el('linearGradient', { id: 'micShineG', x1: 0, y1: 0, x2: 1, y2: 0 }, defs);
  el('stop', { offset: 0, 'stop-color': '#fff', 'stop-opacity': 0 }, shGrad);
  el('stop', { offset: .5, 'stop-color': '#fff', 'stop-opacity': .55 }, shGrad);
  el('stop', { offset: 1, 'stop-color': '#fff', 'stop-opacity': 0 }, shGrad);
  var shm = el('g', { mask: 'url(#micShineMask)' }, gShine);
  var shine = el('rect', { x: -300, y: -20, width: 240, height: BH + 40, fill: 'url(#micShineG)', transform: 'skewX(-18)' }, shm);

  var gWin = el('g', { 'class': 'mic-win', opacity: 0, style: 'mix-blend-mode:screen' }, root);
  WINDOWS.forEach(function (w, k) {
    var e = el('ellipse', { cx: w[0] + w[2] / 2, cy: w[1] + w[3] / 2, rx: w[2] * 0.62, ry: w[3] * 0.78, fill: 'url(#micWarm)', 'class': 'mic-wglow' }, gWin);
    e.style.animationDelay = (k * 0.9).toFixed(1) + 's'; e.style.animationDuration = (4.5 + k * 0.7).toFixed(1) + 's';
  });

  /* ---------- partikel cahaya naik ---------- */
  var gP = el('g', { 'class': 'mic-parts', opacity: 0 }, root);
  for (var q = 0; q < 26; q++) {
    var px = (rnd() * (BW + 160) - 80).toFixed(0), py0 = BH - 20 - rnd() * 40;
    var c = el('circle', { cx: px, cy: py0, r: (1.4 + rnd() * 2.4).toFixed(1), fill: rnd() < 0.5 ? '#ffd98a' : '#93c5fd', opacity: 0 }, gP);
    var dur = (7 + rnd() * 8).toFixed(1), begin = (rnd() * 8).toFixed(1);
    el('animate', { attributeName: 'cy', from: py0, to: (py0 - 260 - rnd() * 260).toFixed(0), dur: dur + 's', begin: begin + 's', repeatCount: 'indefinite' }, c);
    el('animate', { attributeName: 'opacity', values: '0;.8;0', dur: dur + 's', begin: begin + 's', repeatCount: 'indefinite' }, c);
  }

  /* ---------- animasi ---------- */
  var t0 = null, finished = false;
  function frame(now) {
    if (t0 === null) t0 = now;
    var t = reduce ? 99 : (now - t0) / 1000;
    items.forEach(function (it) {
      var p = it.p, r = p.region, e = ease((t - p.at) / p.dur);
      if (p.dir === 'up') {
        var h = r[3] * e; it.ar.setAttribute('y', (r[1] + r[3] - h).toFixed(1)); it.ar.setAttribute('height', h.toFixed(1));
        it.line.setAttribute('y', (r[1] + r[3] - h - 4).toFixed(1)); it.line.setAttribute('x', r[0]);
      } else {
        var w = r[2] * e; it.ar.setAttribute('y', r[1]); it.ar.setAttribute('height', r[3]); it.ar.setAttribute('width', w.toFixed(1));
        it.line.setAttribute('x', (r[0] + w - 4).toFixed(1)); it.line.setAttribute('y', r[1]);
      }
      if (p.dir === 'up') it.ar.setAttribute('width', r[2]);
      var live = (t - p.at) > 0 && e < 1;
      it.line.setAttribute('opacity', 0); /* tanpa garis cahaya */
    });
    var f = ease((t - 2.6) / 1.6);
    gRef.setAttribute('opacity', f.toFixed(2));
    var g = ease((t - GLOW_AT) / 1.3);
    gWin.setAttribute('opacity', g.toFixed(2)); gShine.setAttribute('opacity', g.toFixed(2)); gP.setAttribute('opacity', g.toFixed(2));
    if (!reduce && t > GLOW_AT) {
      var k = ((t - GLOW_AT) % 6.5) / 6.5;
      shine.setAttribute('x', (-320 + k * (BW + 700)).toFixed(0));
    } else if (reduce) { shine.setAttribute('x', -500); }
    if (t < TAIL || (!reduce && finished === false && t < TAIL + 1)) requestAnimationFrame(frame);
    else if (!finished) { finished = true; svg.classList.add('is-live'); if (!reduce) requestAnimationFrame(loopShine); }
  }
  function loopShine(now) {
    if (t0 === null) return;
    var t = (now - t0) / 1000, k = ((t - GLOW_AT) % 6.5) / 6.5;
    shine.setAttribute('x', (-320 + k * (BW + 700)).toFixed(0));
    if (!document.hidden) requestAnimationFrame(loopShine); else document.addEventListener('visibilitychange', function once() { document.removeEventListener('visibilitychange', once); requestAnimationFrame(loopShine); });
  }
  requestAnimationFrame(frame);
  function replay() {
    if (reduce) return;
    t0 = null; finished = false; svg.classList.remove('is-live');
    requestAnimationFrame(frame);
  }
  svg.style.cursor = 'pointer';
  svg.addEventListener('click', replay);
  window.MICScene = { replay: replay };
  if (reduce) svg.classList.add('is-live');

  /* ---------- parallax kursor ---------- */
  if (!reduce) {
    var mx = 0, my = 0, tx = 0, ty = 0, ticking = false;
    window.addEventListener('pointermove', function (e) {
      tx = (e.clientX / window.innerWidth - 0.5); ty = (e.clientY / window.innerHeight - 0.5);
      if (!ticking) { ticking = true; requestAnimationFrame(loop); }
    }, { passive: true });
    function loop() {
      mx += (tx - mx) * 0.1; my += (ty - my) * 0.1;
      gB.style.transform = 'translate(' + (mx * -14).toFixed(1) + 'px,' + (my * -8).toFixed(1) + 'px)';
      gRef.style.transform = 'translate(' + (mx * -14).toFixed(1) + 'px,' + (my * -8).toFixed(1) + 'px)';
      gWin.style.transform = gP.style.transform = gShine.style.transform = gB.style.transform;
      gBack.style.transform = 'translate(' + (mx * 8).toFixed(1) + 'px,' + (my * 5).toFixed(1) + 'px)';
      if (Math.abs(tx - mx) > 0.002 || Math.abs(ty - my) > 0.002) requestAnimationFrame(loop); else ticking = false;
    }
  }
})();
