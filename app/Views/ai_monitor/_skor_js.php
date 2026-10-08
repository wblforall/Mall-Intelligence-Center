<?php
/** Chart.js untuk panel skor mutu prompt (karyawan). Butuh: $skor, $skor_tren. */
use App\Libraries\AiSkorPrompt;
$dimLabel = array_map(fn($d) => $d['nama'], AiSkorPrompt::DIMENSI);
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';
    const indigo = dark ? '#818cf8' : '#6366f1', cyan = dark ? '#22d3ee' : '#0891b2';
    const inkMuted = dark ? 'rgba(221,232,248,.65)' : 'rgba(33,37,41,.65)';
    const gridCol  = dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    const hexA = (hex, a) => { const n = parseInt(hex.slice(1), 16); return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`; };

    const tren = <?= json_encode($skor_tren) ?>;
    const tEl = document.getElementById('skTren');
    if (tEl) {
        new Chart(tEl, {
            type: 'line',
            data: { labels: tren.map(d => d.label), datasets: [{
                label: 'Skor rata-rata', data: tren.map(d => d.rata), spanGaps: true,
                borderColor: indigo, backgroundColor: hexA(indigo, .15), fill: true, tension: .3, borderWidth: 2,
                pointRadius: tren.map(d => d.rata === null ? 0 : 3), pointBackgroundColor: indigo,
            }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: {
                    title: (it) => 'Minggu mulai ' + it[0].label,
                    label: (c) => 'Rata-rata ' + c.parsed.y + ' (dari ' + tren[c.dataIndex].n + ' sesi)',
                } } },
                scales: {
                    x: { ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
                    y: { min: 0, max: 100, ticks: { color: inkMuted, font: { size: 10 } }, grid: { color: gridCol } },
                },
            },
        });
    }

    const dEl = document.getElementById('skDimensi');
    if (dEl) {
        const dim = <?= json_encode(array_values($skor['dimensi'])) ?>;
        new Chart(dEl, {
            type: 'radar',
            data: { labels: <?= json_encode(array_values($dimLabel)) ?>, datasets: [{
                label: 'Rata-rata', data: dim, borderColor: cyan, backgroundColor: hexA(cyan, .2), borderWidth: 2, pointBackgroundColor: cyan,
            }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { r: { min: 0, max: 20, ticks: { stepSize: 5, color: inkMuted, backdropColor: 'transparent', font: { size: 9 } },
                    grid: { color: gridCol }, angleLines: { color: gridCol }, pointLabels: { color: inkMuted, font: { size: 10 } } } },
            },
        });
    }
});
</script>
