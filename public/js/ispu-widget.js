/**
 * Widget ISPU Balikpapan (Dashboard MIC). Mengambil dashboard/ispu, menggambar:
 *  - gauge setengah lingkaran 0–300+ (lima kategori resmi) dengan nilai & kategori,
 *  - tab stasiun (Balikpapan Sepinggan / Balikpapan Baru),
 *  - batang sub-indeks per parameter (PM10, PM2.5, SO2, CO, O3, NO2, HC),
 *  - Indikator asap (karhutla): PM2.5 utama, PM10 pendamping, CO penguat + status gabungan,
 *    tren/sparkline dari rekaman MIC (data.riwayat), banner peringatan (data.asap.banner),
 *  - saran kesehatan umum + keterangan resmi, waktu data (WITA), peringatan data lama.
 * Semua teks dari sumber di-escape (textContent / esc). Segarkan otomatis tiap 10 menit.
 */
(function () {
  'use strict';
  var root = document.getElementById('ispuWidget');
  if (!root) return;
  var body = document.getElementById('ispuBody');
  var foot = document.getElementById('ispuFootInfo');
  var live = document.getElementById('ispuLive');
  var liveTxt = document.getElementById('ispuLiveTxt');
  var URL_DATA = root.getAttribute('data-url');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var data = null, aktif = 0, timer = null;

  var SEG = [
    { a: 0,   b: 50,  w: '#16a34a' },
    { a: 50,  b: 100, w: '#2563eb' },
    { a: 100, b: 200, w: '#d97706' },
    { a: 200, b: 300, w: '#dc2626' }
  ];
  var MAX = 300;

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  function polar(cx, cy, r, deg) { var t = (180 - deg) * Math.PI / 180; return [cx + r * Math.cos(t), cy - r * Math.sin(t)]; }
  function arc(cx, cy, r, d0, d1) {
    var p0 = polar(cx, cy, r, d0), p1 = polar(cx, cy, r, d1);
    return 'M' + p0[0].toFixed(2) + ' ' + p0[1].toFixed(2) + ' A' + r + ' ' + r + ' 0 0 1 ' + p1[0].toFixed(2) + ' ' + p1[1].toFixed(2);
  }
  function waktu(iso) {
    if (!iso) return '—';
    var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(iso); if (!m) return '—';
    var bln = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return parseInt(m[3], 10) + ' ' + bln[parseInt(m[2], 10) - 1] + ' ' + m[1] + ', ' + m[4] + '.' + m[5] + ' WITA';
  }
  function umurJam(iso) { if (!iso) return null; var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(iso); if (!m) return null; var t = Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4] - 8, +m[5]); return (Date.now() - t) / 3600000; }

  function gauge(s) {
    var v = s.nilai == null ? 0 : s.nilai, cx = 110, cy = 104, r = 86;
    var svg = '<svg viewBox="0 0 220 128" class="ispu-gauge" role="img" aria-label="ISPU ' + esc(s.nilai) + ', ' + esc(s.kategori) + '">';
    svg += '<path d="' + arc(cx, cy, r, 0, 180) + '" class="ispu-track"/>';
    SEG.forEach(function (g) {
      svg += '<path d="' + arc(cx, cy, r, g.a / MAX * 180 + 1.2, g.b / MAX * 180 - 1.2) + '" stroke="' + g.w + '" class="ispu-seg"/>';
    });
    var pos = Math.min(v, MAX) / MAX * 180, np = polar(cx, cy, r, pos), ni = polar(cx, cy, r - 17, pos);
    svg += '<line class="ispu-needle" x1="' + ni[0].toFixed(1) + '" y1="' + ni[1].toFixed(1) + '" x2="' + np[0].toFixed(1) + '" y2="' + np[1].toFixed(1) + '"/>';
    svg += '<circle class="ispu-knob" cx="' + np[0].toFixed(1) + '" cy="' + np[1].toFixed(1) + '" r="6.5" style="fill:' + esc(s.warna) + '"/>';
    svg += '<text x="' + cx + '" y="' + (cy - 14) + '" class="ispu-num" text-anchor="middle" data-nilai="' + esc(s.nilai) + '">' + esc(s.nilai == null ? '—' : Math.round(s.nilai)) + '</text>';
    svg += '<text x="' + cx + '" y="' + (cy + 4) + '" class="ispu-numlbl" text-anchor="middle">ISPU</text>';
    svg += '<text x="' + (cx - r) + '" y="' + (cy + 17) + '" class="ispu-tick" text-anchor="middle">0</text>';
    svg += '<text x="' + (cx + r) + '" y="' + (cy + 17) + '" class="ispu-tick" text-anchor="middle">300+</text>';
    return svg + '</svg>';
  }

  function params(s) {
    if (!s.parameter || !s.parameter.length) return '<p class="ispu-empty">Sub-indeks parameter belum tersedia dari stasiun ini.</p>';
    return '<ul class="ispu-params">' + s.parameter.map(function (p) {
      var w = Math.max(3, Math.min(100, p.indeks / MAX * 100));
      var kritis = s.kritis && s.kritis.replace(/\s/g, '').toUpperCase() === String(p.label).replace(/\s/g, '').toUpperCase();
      return '<li' + (kritis ? ' class="is-kritis"' : '') + '><span class="ispu-pl">' + esc(p.label) + (kritis ? ' <em>kritis</em>' : '') + '</span>' +
        '<span class="ispu-pb"><i style="width:' + w.toFixed(1) + '%;background:' + esc(p.warna) + '" data-w="' + w.toFixed(1) + '"></i></span>' +
        '<span class="ispu-pv" style="color:' + esc(p.warna) + '">' + esc(Math.round(p.indeks)) + '</span>' +
        '<span class="ispu-pk">' + esc(p.kategori) + '</span></li>';
    }).join('') + '</ul>';
  }

  /* ───────── Indikator asap (karhutla): PM2.5 utama, PM10 pendamping, CO penguat ───────── */
  var SK = 'mic_ispu_asap_tutup', RANK = { kuning: 1, merah: 2 };
  function fmt(n) { return n == null ? '—' : String(Math.round(n * 10) / 10).replace('.', ','); }
  function fmtSel(n) { return (n > 0 ? '+' : n < 0 ? '−' : '±') + fmt(Math.abs(n)); }
  function ikon(t) { return t.arah === 'naik' ? '▲' : t.arah === 'turun' ? '▼' : '▬'; }
  function trenTeks(t) {
    if (!t) return 'tren tersedia setelah beberapa jam';
    return (t.arah === 'naik' ? 'Naik ' : t.arah === 'turun' ? 'Turun ' : 'Stabil ') + fmtSel(t.selisih) + ' poin dibanding ±' + fmt(t.jam) + ' jam lalu';
  }
  function tipInd(b) {
    if (!b.tersedia) return b.catatan || (b.label + ' tidak tersedia');
    return b.label + ' (' + b.peran + '): sub-indeks ' + Math.round(b.indeks) + ' ' + b.kategori +
      (b.konsentrasi != null ? ', ' + fmt(parseFloat(b.konsentrasi)) + ' ' + b.satuan : '') + ' · ' + trenTeks(b.tren);
  }
  function pil(b) {
    if (!b.tersedia) return '<span class="ispu-pill is-na" title="' + esc(tipInd(b)) + '"><b>' + esc(b.label) + '</b> —</span>';
    return '<span class="ispu-pill" style="--p:' + esc(b.warna) + '" title="' + esc(tipInd(b)) + '"><b>' + esc(b.label) + '</b> ' + esc(Math.round(b.indeks)) +
      (b.tren && b.tren.arah !== 'stabil' ? '<u class="t-' + esc(b.tren.arah) + '">' + ikon(b.tren) + '</u>' : '') + '</span>';
  }
  function chipAsap(a, kls) {
    return '<span class="ispu-ast ' + (kls || '') + (String(a.singkat).length > 15 && !kls ? ' lg' : '') + '" style="--c:' + esc(a.warna) + '"><i></i><span>' + esc(a.singkat) + '</span></span>';
  }
  function spark(seri, warna, label) {
    if (!seri || seri.length < 2) return '';
    var W = 120, H = 30, p = 3, t0 = seri[0][0], t1 = seri[seri.length - 1][0], lo = Infinity, hi = -Infinity;
    seri.forEach(function (q) { lo = Math.min(lo, q[1]); hi = Math.max(hi, q[1]); });
    if (hi - lo < 10) { var m = (hi + lo) / 2; lo = m - 5; hi = m + 5; }
    var pts = seri.map(function (q) {
      var x = p + (t1 === t0 ? 0 : (q[0] - t0) / (t1 - t0)) * (W - 2 * p), y = H - p - (q[1] - lo) / (hi - lo) * (H - 2 * p);
      return [x, y];
    });
    var line = pts.map(function (q) { return q[0].toFixed(1) + ',' + q[1].toFixed(1); }).join(' '), l = pts[pts.length - 1];
    var jam = Math.max(0.1, (t1 - t0) / 3600);
    return '<svg class="ispu-spark" viewBox="0 0 ' + W + ' ' + H + '" role="img" style="--c:' + esc(warna) + '" aria-label="' + esc(label + ' ' + fmt(jam) + ' jam terakhir (' + seri.length + ' titik), kini ' + Math.round(seri[seri.length - 1][1])) + '">' +
      '<title>' + esc(label + ' · ' + fmt(jam) + ' jam terakhir · ' + seri.length + ' titik rekaman MIC') + '</title>' +
      '<polygon class="sp-area" points="' + p + ',' + (H - p) + ' ' + line + ' ' + l[0].toFixed(1) + ',' + (H - p) + '"/>' +
      '<polyline class="sp-line" points="' + line + '"/><circle class="sp-dot" cx="' + l[0].toFixed(1) + '" cy="' + l[1].toFixed(1) + '" r="2.6"/></svg>';
  }
  function kartuInd(b) {
    if (!b.tersedia) {
      return '<div class="ispu-ic is-na"><div class="ispu-ic-h"><b>' + esc(b.label) + '</b><em>' + esc(b.peran) + '</em></div>' +
        '<div class="ispu-ic-v">—</div><div class="ispu-ic-s">' + esc(b.catatan || 'tidak tersedia') + '</div></div>';
    }
    var t = b.tren;
    return '<div class="ispu-ic" style="--p:' + esc(b.warna) + '"><div class="ispu-ic-h"><b>' + esc(b.label) + '</b><em>' + esc(b.peran) + '</em></div>' +
      '<div class="ispu-ic-v"><strong>' + esc(Math.round(b.indeks)) + '</strong><span class="ispu-ic-k">' + esc(b.kategori) + '</span></div>' +
      '<div class="ispu-ic-s">' + (b.konsentrasi != null ? '<b>' + esc(fmt(parseFloat(b.konsentrasi))) + '</b> ' + esc(b.satuan) : 'konsentrasi tidak tersedia') + '</div>' +
      '<div class="ispu-ic-t' + (t ? ' t-' + esc(t.arah) : '') + '">' + (t ? '<u>' + ikon(t) + '</u> ' : '') + esc(trenTeks(t)) + '</div>' +
      (b.kode === 'pm25' ? spark(b.seri, b.warna, 'PM2.5') : '') + '</div>';
  }
  function blokAsap(s) {
    var a = s.asap; if (!a) return '';
    var rw = data.riwayat || {};
    var cat = (a.catatan || []).map(function (c) { return '<div class="ispu-note"><i class="bi bi-info-circle"></i><span>' + esc(c) + '</span></div>'; }).join('');
    return '<div class="ispu-asap" style="--c:' + esc(a.warna) + '"><div class="ispu-asap-h"><span class="ispu-rtitle">Indikator asap (karhutla) · ' + esc(s.nama) + '</span>' + chipAsap(a, 'big') + '</div>' +
      '<div class="ispu-ics">' + a.indikator.map(kartuInd).join('') + '</div>' +
      '<div class="ispu-asap-saran"><i class="bi bi-' + (a.level >= 2 ? 'shield-exclamation' : 'shield-check') + '"></i><span><b>' + esc(a.label) + '.</b> ' + esc(a.saran) + '</span></div>' + cat +
      '<p class="ispu-asap-src"><i class="bi bi-database"></i><span>PM2.5 = parameter utama asap, PM10 pendamping, CO penguat (pembakaran); SO₂/NO₂/O₃ bukan indikator asap. ' +
      (rw.mulai_wita ? 'Tren dihitung dari rekaman MIC sejak <b>' + esc(waktu(rw.mulai_wita)) + '</b> (' + esc(rw.jumlah) + ' titik), bukan dari sumber resmi.' : 'Tren dihitung dari rekaman MIC (belum ada rekaman), bukan dari sumber resmi.') +
      (!rw.cukup ? ' Tren tersedia setelah beberapa jam.' : '') + '</span></p></div>';
  }

  var banner = document.getElementById('ispuBanner');
  function tutupTersimpan() { try { return sessionStorage.getItem(SK) || ''; } catch (e) { return ''; } }
  function renderBanner() {
    if (!banner) return;
    var b = data && data.asap && data.asap.banner;
    if (!b || (b.boleh_tutup && (RANK[tutupTersimpan()] || 0) >= RANK[b.tingkat])) { banner.hidden = true; banner.innerHTML = ''; return; }
    banner.hidden = false;
    banner.className = 'ispu-banner is-' + b.tingkat;
    banner.setAttribute('role', b.tingkat === 'merah' ? 'alert' : 'status');
    banner.innerHTML = '<i class="bi bi-' + (b.tingkat === 'merah' ? 'exclamation-octagon-fill' : 'exclamation-triangle-fill') + ' ispu-bn-ico"></i>' +
      '<div class="ispu-bn-tx"><b>' + esc(b.judul) + '</b><span>' + esc(b.teks) + '</span><span class="ispu-bn-sr">' + esc(b.saran) + '</span></div>' +
      (b.boleh_tutup ? '<button type="button" class="ispu-bn-x" aria-label="Tutup peringatan untuk sesi ini" title="Tutup (sampai sesi browser berakhir)"><i class="bi bi-x-lg"></i></button>' : '');
    var x = banner.querySelector('.ispu-bn-x');
    if (x) x.addEventListener('click', function () { try { sessionStorage.setItem(SK, b.tingkat); } catch (e) {} renderBanner(); });
  }
  var headChip = document.getElementById('ispuAsapChip');
  function renderChip() {
    if (!headChip) return;
    var a = data && data.asap;
    if (!a || a.level < 0) { headChip.hidden = true; return; }
    headChip.hidden = false; headChip.innerHTML = '<em>Asap</em>' + chipAsap(a);
    headChip.title = 'Status asap karhutla (terburuk di stasiun resmi): ' + a.label;
  }

  var KEY = 'mic_ispu_detail';
  function bukaDetail() { try { return localStorage.getItem(KEY) === '1'; } catch (e) { return false; } }
  function simpanDetail(v) { try { localStorage.setItem(KEY, v ? '1' : '0'); } catch (e) {} }

  var TIP_EST = 'Bukan pengukuran langsung: perkiraan dari 2 stasiun resmi terdekat (interpolasi berbobot jarak), E-Walk & Pentacity';
  function tipEst(x) { return x.metode || TIP_EST.replace('2 stasiun', (x.jumlah_stasiun || 2) + ' stasiun'); }
  function sumberTeks(x) {
    return (x.sumber_stasiun || []).map(function (q) { return q.nama + ' ' + String(q.jarak_km).replace('.', ',') + ' km'; }).join(' · ');
  }

  function ubin(x, i) {
    var jam = umurJam(x.waktu_wita), lama = jam != null && jam > 3, est = !!x.estimasi, a = x.asap;
    var tip = (est ? tipEst(x) + ' · ' : '') + 'Data per ' + waktu(x.waktu_wita) + (est ? ' · dari ' + (x.jumlah_stasiun || (x.sumber_stasiun || []).length) + ' stasiun' : '');
    return '<button type="button" class="ispu-tile' + (est ? ' is-est' : '') + (a ? ' has-asap' : '') + (i === aktif ? ' on' : '') + '" data-i="' + i + '" style="--c:' + esc(x.warna) + '" aria-pressed="' + (i === aktif) + '" title="' + esc(tip) + '">' +
      '<span class="ispu-tnum">' + esc(x.nilai == null ? '—' : Math.round(x.nilai)) + '</span>' +
      '<span class="ispu-tinfo"><b>' + (est ? esc(x.nama.replace(' · estimasi', '')) + '<span class="ispu-xs"> · estimasi</span> <span class="ispu-badge">Estimasi</span>' : esc(x.nama)) + '</b>' +
        '<span><em class="ispu-tcat">' + esc(x.kategori || '—') + '</em> · kritis ' + esc(x.kritis || '—') + (lama ? ' · <span class="ispu-old">data lama</span>' : '') + '</span>' +
        (a ? '<span class="ispu-asaprow">' + chipAsap(a) + '</span>'
           : '<small>' + esc(waktu(x.waktu_wita)) + (lama ? ' · data lama' : '') + '</small>') + '</span>' +
      (a ? '<span class="ispu-asapcol" aria-label="Indikator asap">' + a.indikator.map(pil).join('') + '</span>' : '') + '</button>';
  }

  function render() {
    if (!data || !data.ok || !data.stasiun.length) {
      body.innerHTML = '<div class="ispu-fail"><i class="bi bi-cloud-slash"></i><div><b>Data ISPU belum tersedia.</b><span>' + esc((data && data.galat) || 'Sumber resmi tidak dapat dihubungi. Dicoba lagi otomatis.') + '</span></div></div>';
      live.hidden = true; foot.textContent = 'Sumber: ISPU KLHK';
      renderBanner(); renderChip();
      return;
    }
    if (aktif >= data.stasiun.length) aktif = 0;
    var s = data.stasiun[aktif], jam = umurJam(s.waktu_wita);
    var lama = jam != null && jam > 3;
    var detail = bukaDetail();

    var maxP = null;
    (s.parameter || []).forEach(function (p) { if (!maxP || p.indeks > maxP.indeks) maxP = p; });
    var catatan = '', est = !!s.estimasi;
    if (est) {
      catatan += '<div class="ispu-note est"><i class="bi bi-exclamation-triangle"></i><span><b>Estimasi, bukan data resmi.</b> ' + esc(tipEst(s)) +
        ' Sumber: ' + esc(sumberTeks(s)) + '. Bobot = 1/jarak²' + ((s.sumber_stasiun || []).length > 1 ? ': ' + (s.sumber_stasiun || []).map(function (q) { return esc(q.nama) + ' ' + esc(String(q.bobot_persen).replace('.', ',')) + '% (ISPU ' + esc(Math.round(q.nilai)) + ')'; }).join(', ') : '') +
        '. Sub-indeks hanya untuk parameter yang tersedia di semua stasiun yang dipakai; konsentrasi tidak diperkirakan.</span></div>';
    }
    if (est && maxP && s.nilai != null && maxP.indeks > s.nilai + 0.5) {
      catatan += '<div class="ispu-note"><i class="bi bi-info-circle"></i><span>Sub-indeks estimasi tertinggi: <b>' + esc(maxP.label) + ' ' + esc(Math.round(maxP.indeks)) + '</b> (' + esc(maxP.kategori) + '), lebih tinggi dari ISPU estimasi (' + esc(Math.round(s.nilai)) + ').</span></div>';
    } else if (maxP && s.nilai != null && maxP.indeks > s.nilai + 0.5) {
      catatan += '<div class="ispu-note"><i class="bi bi-info-circle"></i><span>Indeks parameter tertinggi di stasiun ini: <b>' + esc(maxP.label) + ' ' + esc(Math.round(maxP.indeks)) + '</b> (' + esc(maxP.kategori) + '), lebih tinggi dari nilai ISPU stasiun (' + esc(Math.round(s.nilai)) + '). Angka ditampilkan sesuai sumber resmi.</span></div>';
    }
    if (s.pemeliharaan) catatan += '<div class="ispu-note warn"><i class="bi bi-tools"></i><span>Stasiun sedang dalam pemeliharaan; data bisa tidak mutakhir.</span></div>';
    if (lama) catatan += '<div class="ispu-note warn"><i class="bi bi-clock-history"></i><span>Data stasiun ini terakhir diperbarui sekitar ' + Math.round(jam) + ' jam lalu.</span></div>';
    if (data.basi) catatan += '<div class="ispu-note warn"><i class="bi bi-wifi-off"></i><span>Sumber resmi sedang tidak terjangkau — menampilkan data terakhir yang berhasil diambil (' + esc(data.umur_menit) + ' menit lalu).</span></div>';

    body.innerHTML =
      '<div class="ispu-tiles">' + data.stasiun.map(ubin).join('') +
        '<button type="button" class="ispu-more" id="ispuMore" aria-expanded="' + detail + '"><span>' + (detail ? 'Tutup detail' : 'Detail') + '</span><i class="bi bi-chevron-' + (detail ? 'up' : 'down') + '"></i></button></div>' +
      '<div class="ispu-detail" id="ispuDetail"' + (detail ? '' : ' hidden') + '>' +
        '<div class="ispu-grid">' +
          '<div class="ispu-left">' + gauge(s) +
            '<div class="ispu-cat" style="--c:' + esc(s.warna) + '"><i></i>' + esc(s.kategori || '—') + '</div>' +
            '<div class="ispu-meta">Parameter kritis' + (est ? ' (estimasi)' : '') + ': <b>' + esc(s.kritis || '—') + '</b></div>' +
          '</div>' +
          '<div class="ispu-right"><div class="ispu-rtitle">Sub-indeks per parameter · ' + esc(s.nama) + '</div>' + params(s) + '</div>' +
        '</div>' +
        blokAsap(s) +
        '<div class="ispu-advice" style="--c:' + esc(s.warna) + '"><i class="bi bi-heart-pulse"></i><div>' +
          '<b>Saran untuk pengunjung & operasional</b><span>' + esc(s.saran || s.keterangan || '') + '</span></div></div>' +
        catatan +
      '</div>';

    foot.textContent = est
      ? 'Estimasi ' + s.nama.replace(' · estimasi', '') + ' (bukan pengukuran) dari ' + (s.sumber_stasiun || []).map(function (q) { return q.nama; }).join(' & ') + ' · data per ' + waktu(s.waktu_wita) + ' · diolah dari ISPU KLHK'
      : 'Stasiun ' + s.nama + (s.tipe ? ' (' + s.tipe + ')' : '') + ' · data per ' + waktu(s.waktu_wita) + ' · sumber: ISPU KLHK';
    renderBanner(); renderChip();
    live.hidden = false; liveTxt.textContent = lama || data.basi ? 'data lama' : 'live';
    live.classList.toggle('is-old', !!(lama || data.basi));
    root.classList.toggle('is-open', detail);

    Array.prototype.forEach.call(body.querySelectorAll('.ispu-tile'), function (b) {
      b.addEventListener('click', function () { aktif = +b.getAttribute('data-i'); render(); });
    });
    var more = document.getElementById('ispuMore');
    if (more) more.addEventListener('click', function () { simpanDetail(!bukaDetail()); render(); });
    if (!reduce && detail) animasi();
  }

  function animasi() {
    var n = body.querySelector('.ispu-num'); if (n) {
      var t = parseFloat(n.getAttribute('data-nilai')), t0 = null; if (!isNaN(t)) {
        n.textContent = '0';
        (function step(ts) { if (t0 === null) t0 = ts; var k = Math.min(1, (ts - t0) / 800); n.textContent = Math.round(t * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); else n.textContent = Math.round(t); })(performance.now());
      }
    }
    Array.prototype.forEach.call(body.querySelectorAll('.ispu-pb i'), function (b) { var w = b.getAttribute('data-w'); b.style.width = '0'; requestAnimationFrame(function () { requestAnimationFrame(function () { b.style.width = w + '%'; }); }); });
  }

  function muat(segarkan) {
    var url = URL_DATA + (segarkan ? '?segarkan=1' : '');
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (j) { data = j; render(); })
      .catch(function () { if (!data) { data = { ok: false, galat: 'Tidak dapat memuat data ISPU.' }; render(); } });
  }

  muat(false);
  timer = setInterval(function () { if (!document.hidden) muat(false); }, 10 * 60 * 1000);
  var btn = document.getElementById('ispuRefresh');
  if (btn) btn.addEventListener('click', function () { btn.disabled = true; btn.classList.add('spin'); muat(true).then(function () { btn.disabled = false; btn.classList.remove('spin'); }); });
})();
