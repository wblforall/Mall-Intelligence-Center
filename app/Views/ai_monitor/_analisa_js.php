<?php
/** Chart.js untuk panel analisa individu. Disisipkan di section 'scripts'.
    Butuh: $analisa. Palet C konsisten dashboard (termasuk 'violet'). */
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    const dark = document.documentElement.getAttribute('data-theme') === 'dark';

    // Palet CVD-safe (light/dark) — konsisten dashboard.
    const C = {
        indigo: dark ? '#818cf8' : '#6366f1',
        cyan:   dark ? '#22d3ee' : '#0891b2',
        green:  dark ? '#199e70' : '#1baf7a',
        amber:  dark ? '#c98500' : '#eda100',
        red:    '#d03b3b',
        violet: dark ? '#a78bfa' : '#7c3aed',
        slate:  dark ? '#94a3b8' : '#64748b',
    };
    const surface  = dark ? '#0e1a2a' : '#ffffff';
    const inkMuted = dark ? 'rgba(221,232,248,.65)' : 'rgba(33,37,41,.65)';
    const gridCol  = dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    const hexA = (hex, a) => {
        const n = parseInt(hex.slice(1), 16);
        return `rgba(${(n>>16)&255},${(n>>8)&255},${n&255},${a})`;
    };
    const donutOpts = {
        responsive: true, maintainAspectRatio: false, cutout: '58%',
        plugins: { legend: { position: 'bottom', labels: { color: inkMuted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 10 } } } },
    };

    // ── Jenis aktivitas (donut) ──
    const jEl = document.getElementById('anJenis');
    if (jEl) {
        const jenisWarna = { coding: C.indigo, debugging: C.red, ideating: C.violet, menulis: C.cyan, riset: C.amber, lainnya: C.slate, Belum: dark ? '#4b5563' : '#cbd5e1' };
        const jenisLabel = { coding: 'Coding', debugging: 'Debugging', ideating: 'Ideating', menulis: 'Menulis', riset: 'Riset', lainnya: 'Lainnya', Belum: 'Belum' };
        const jc = <?= json_encode($analisa['jenis']) ?>;
        const jk = Object.keys(jc);
        new Chart(jEl, {
            type: 'doughnut',
            data: { labels: jk.map(k => jenisLabel[k] || k), datasets: [{ data: jk.map(k => jc[k]), backgroundColor: jk.map(k => jenisWarna[k] || C.slate), borderColor: surface, borderWidth: 2 }] },
            options: donutOpts,
        });
    }

    // ── Top tema (bar horizontal) ──
    const tEl = document.getElementById('anTema');
    if (tEl) {
        const tema = <?= json_encode($analisa['tema']) ?>;
        new Chart(tEl, {
            type: 'bar',
            data: { labels: tema.map(r => r.tema), datasets: [{ label: 'Sesi', data: tema.map(r => r.jumlah), backgroundColor: hexA(C.violet, .85), borderColor: C.violet, borderWidth: 1, borderRadius: 5 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } }, y: { ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } } },
            },
        });
    }

    // ── Kantor vs pribadi (donut) ──
    const kEl = document.getElementById('anKantor');
    if (kEl) {
        const kantorWarna = { kantor: C.green, pribadi: C.amber, tak_jelas: C.slate, Belum: dark ? '#4b5563' : '#cbd5e1' };
        const kantorLabel = { kantor: 'Kantor', pribadi: 'Pribadi', tak_jelas: 'Tak jelas', Belum: 'Belum' };
        const kc = <?= json_encode($analisa['kantor']) ?>;
        const kk = Object.keys(kc);
        new Chart(kEl, {
            type: 'doughnut',
            data: { labels: kk.map(k => kantorLabel[k] || k), datasets: [{ data: kk.map(k => kc[k]), backgroundColor: kk.map(k => kantorWarna[k] || C.slate), borderColor: surface, borderWidth: 2 }] },
            options: donutOpts,
        });
    }

    // ── Tren harian (prompt batang/garis + token garis sumbu kanan) ──
    const trEl = document.getElementById('anTren');
    if (trEl) {
        const deret = <?= json_encode($analisa['tren']) ?>;
        new Chart(trEl, {
            data: {
                labels: deret.map(d => d.label),
                datasets: [
                    { type: 'line', label: 'Prompt', yAxisID: 'y', data: deret.map(d => d.prompt), borderColor: C.indigo, backgroundColor: hexA(C.indigo, .18), fill: true, tension: .35, borderWidth: 2, pointRadius: 2, pointHoverRadius: 4, pointBackgroundColor: C.indigo },
                    { type: 'line', label: 'Token', yAxisID: 'y1', data: deret.map(d => d.token), borderColor: C.cyan, backgroundColor: hexA(C.cyan, .12), fill: true, tension: .35, borderWidth: 2, borderDash: [5, 4], pointRadius: 2, pointHoverRadius: 4, pointBackgroundColor: C.cyan },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { color: inkMuted, usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, font: { size: 11 } } } },
                scales: {
                    x:  { ticks: { color: inkMuted, font: { size: 10 } }, grid: { display: false } },
                    y:  { position: 'left',  beginAtZero: true, title: { display: true, text: 'Prompt', color: inkMuted, font: { size: 10 } }, ticks: { color: inkMuted, precision: 0, font: { size: 10 } }, grid: { color: gridCol } },
                    y1: { position: 'right', beginAtZero: true, title: { display: true, text: 'Token', color: inkMuted, font: { size: 10 } }, ticks: { color: inkMuted, font: { size: 10 } }, grid: { drawOnChartArea: false } },
                },
            },
        });
    }
});
</script>
