<?php /* Style baku Laporan Bulanan (A4 landscape, font 11px, page-break aman).
         Dipakai laporan print parkir (revenue & kendaraan); pola sama dengan
         laporan loyalty/sponsorship/traffic. */ ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }

@page { size: A4 landscape; margin: 12mm 14mm 10mm; }
@media print {
    .no-print { display: none !important; }
    body { font-size: 11px; }
}

thead { display: table-header-group; }
.main-table tr { break-inside: avoid; page-break-inside: avoid; }
.sec-title { break-after: avoid; page-break-after: avoid; break-inside: avoid; }
.kpi-row, .chart-panel, .sign-row, .duo { break-inside: avoid; page-break-inside: avoid; }

.doc-header {
    border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 16px;
    display: flex; justify-content: space-between; align-items: flex-end;
}
.doc-header .title { font-size: 18px; font-weight: 700; color: #1e293b; }
.doc-header .sub   { font-size: 13px; color: #475569; margin-top: 2px; }
.doc-header .org   { font-size: 10px; color: #94a3b8; margin-top: 5px; }
.doc-header .meta  { text-align: right; font-size: 10px; color: #64748b; line-height: 1.8; }

.kpi-row { display: flex; gap: 10px; margin-bottom: 16px; }
.kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 9px 12px; background: #f8fafc; }
.kpi-label { font-size: 10px; color: #64748b; margin-bottom: 3px; }
.kpi-num   { font-size: 19px; font-weight: 700; line-height: 1.15; }
.kpi-sub   { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }
.kpi-blue  { border-color: #bfdbfe; background: #eff6ff; } .kpi-blue .kpi-num  { color: #1d4ed8; }
.kpi-green { border-color: #bbf7d0; background: #f0fdf4; } .kpi-green .kpi-num { color: #15803d; }
.kpi-amber { border-color: #fde68a; background: #fffbeb; } .kpi-amber .kpi-num { color: #b45309; }
.kpi-purple{ border-color: #c4b5fd; background: #f5f3ff; } .kpi-purple .kpi-num{ color: #6d28d9; }
.delta-up   { color: #15803d; font-weight: 700; }
.delta-down { color: #b91c1c; font-weight: 700; }

.sec-title {
    font-size: 11px; font-weight: 700; color: #f1f5f9; text-transform: uppercase;
    letter-spacing: .4px; background: #1e293b; padding: 5px 10px;
    border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center;
}
.sec-title .sec-sub { font-weight: 400; font-size: 9.5px; opacity: .75; }
.main-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.main-table th {
    background: #334155; color: #f1f5f9; font-size: 10px;
    padding: 5px 7px; border: 1px solid #475569; text-align: left; white-space: nowrap;
}
.main-table th.text-center { text-align: center; }
.main-table td { padding: 4px 7px; border: 1px solid #e2e8f0; font-size: 11px; vertical-align: middle; }
.main-table tr:nth-child(even) td { background: #f8fafc; }
.num  { text-align: right; font-variant-numeric: tabular-nums; }
.zero { color: #cbd5e1; text-align: right; }
.subnote { font-size: 9px; color: #94a3b8; }
.we-row td { background: #fffbeb !important; }
.duo { display: flex; gap: 14px; }
.duo > div { flex: 1; min-width: 0; }

.chart-panel {
    display: flex; gap: 10px; margin-bottom: 18px;
    border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 6px 6px; padding: 10px;
}
.insight-box { flex: 0 0 30%; }
.insight-title { font-size: 10.5px; font-weight: 700; color: #1e293b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .3px; }
.insight-list { margin: 0; padding-left: 14px; }
.insight-list li { font-size: 10.5px; color: #334155; line-height: 1.5; margin-bottom: 5px; }
.chart-box { flex: 1; min-width: 0; }
.chart-title { font-size: 10px; font-weight: 700; color: #475569; margin-bottom: 4px; }
.chart-wrap { height: 165px; position: relative; }

.sign-row { display: flex; gap: 20px; margin-top: 24px; }
.sign-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 9px 12px 10px; text-align: center; font-size: 11px; color: #475569; }
.sign-box .sign-role { font-weight: 700; color: #1e293b; font-size: 11.5px; margin-top: 3px; }
.doc-footer {
    margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 6px;
    display: flex; justify-content: space-between; font-size: 9.5px; color: #94a3b8;
}
.btn-print {
    position: fixed; top: 16px; right: 16px; background: #1e293b; color: #fff;
    border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer;
    font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.btn-print:hover { background: #334155; }
</style>
