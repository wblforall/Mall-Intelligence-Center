<?php /** Chart + pengurutan tabel skor di dashboard. Butuh: $skor. */ ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    const surface  = dark ? '#0e1a2a' : '#ffffff';
    const inkMuted = dark ? 'rgba(221,232,248,.65)' : 'rgba(33,37,41,.65)';
    const sEl = document.getElementById('skSebaran');
    if (sEl && typeof Chart !== 'undefined') {
        const t = <?= json_encode($skor['tingkat']) ?>;
        const ks = ['perlu_dilatih', 'cukup', 'baik', 'sangat_baik'];
        new Chart(sEl, {
            type: 'doughnut',
            data: { labels: ['Perlu dilatih (<40)', 'Cukup (40-69)', 'Baik (70-84)', 'Sangat baik (85+)'],
                datasets: [{ data: ks.map(k => t[k]), borderColor: surface, borderWidth: 2,
                    backgroundColor: [dark ? '#c98500' : '#eda100', dark ? '#22d3ee' : '#0891b2', dark ? '#199e70' : '#1baf7a', dark ? '#818cf8' : '#6366f1'] }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '58%',
                plugins: { legend: { position: 'bottom', labels: { color: inkMuted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 10 } } } } },
        });
    }
    // Urut tabel atas permintaan (klik judul kolom); default abjad, bukan peringkat.
    const tb = document.querySelector('#skTabel tbody');
    if (tb) {
        let arah = {};
        document.querySelectorAll('#skTabel th[data-k]').forEach(th => th.addEventListener('click', () => {
            const k = th.dataset.k; arah[k] = !(arah[k] ?? false);
            const rows = Array.from(tb.querySelectorAll('tr[data-nama]'));
            rows.sort((a, b) => {
                const x = a.dataset[k], y = b.dataset[k];
                if (k === 'nama') return arah[k] ? x.localeCompare(y) : y.localeCompare(x);
                const nx = x === '' ? null : parseFloat(x), ny = y === '' ? null : parseFloat(y);
                if (nx === null && ny === null) return 0; if (nx === null) return 1; if (ny === null) return -1;
                return arah[k] ? ny - nx : nx - ny;
            });
            rows.forEach(r => tb.appendChild(r));
        }));
    }
});
</script>
