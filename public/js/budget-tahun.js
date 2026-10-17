/* MIC — pengaman tahun Budget Departemen.
   1) Tautan pindah tahun/tab/dept (a[data-bdt-nav]) diperingatkan bila isian belum disimpan
      (halaman Isi Budget memasang window.bdKotor).
   2) Input tanggal ber-data-bdt-tahun: pesan tegas bila tanggal di luar tahun halaman. */
(function () {
    window.bdLepas = false;           // true = sudah dikonfirmasi pergi, jangan tanya lagi
    document.addEventListener('click', function (e) {
        const a = e.target.closest && e.target.closest('a[data-bdt-nav]');
        if (!a || e.defaultPrevented || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank') return;
        if (typeof window.bdKotor === 'function' && window.bdKotor()) {
            const th = a.dataset.bdtTujuan || '';
            if (!window.confirm('Ada isian budget yang BELUM DISIMPAN' + (window.bdTahunIni ? ' (Budget ' + window.bdTahunIni + ')' : '') + '.\n' +
                (th ? 'Pindah ke ' + th + ' akan membuangnya. ' : 'Pindah halaman akan membuangnya. ') + 'Lanjut pindah?')) {
                e.preventDefault();
                return;
            }
            window.bdLepas = true;
        }
    });
    function pasangTanggal(inp) {
        const th = inp.dataset.bdtTahun;
        const cek = () => {
            const v = inp.value;
            if (v && !v.startsWith(th + '-')) inp.setCustomValidity('Tanggal harus di tahun ' + th + ' (halaman ini Budget ' + th + '). Pindah tahun dulu bila ini transaksi tahun lain.');
            else inp.setCustomValidity('');
        };
        inp.addEventListener('input', cek); inp.addEventListener('change', cek);
        const f = inp.form;
        if (f) f.addEventListener('submit', cek, true);
        cek();
    }
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('input[data-bdt-tahun]').forEach(pasangTanggal));
})();
