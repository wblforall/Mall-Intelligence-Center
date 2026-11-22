#!/usr/bin/env python3
"""
Buat public/css/skin-executive-peta.css: semua aturan tema Terang (Neo) yang
memakai aksen OREN dipetakan ke navy tema WBL (skin executive).

Kenapa otomatis: tema Neo menulis oranye langsung di ±70 aturan (menu, tab,
tombol, tabel, paginasi, dropdown, kalender, ...). Menimpa satu per satu
dengan tangan selalu ada yang terlewat. Skrip ini membaca aturan
[data-theme="light"] yang mengandung warna oranye, menyalin HANYA deklarasi
beroranye, mengganti warnanya ke palet navy, dan menukar selektornya ke
html[data-skin="executive"] (lebih spesifik → menang atas Neo).

Oranye berarti PERINGATAN (.text-warning, .btn-warning, .bg-warning-subtle,
dsb.) tidak tersentuh: aturan itu bukan [data-theme="light"].

Berkas hasil dimuat SEBELUM skin-executive.css, sehingga aturan WBL yang
ditulis tangan (kartu kaca, sidebar, isian) tetap menang.

Jalankan ulang setelah tema Neo diubah:
    python3 scripts/petakan_neo_ke_wbl.py
"""
import re
from pathlib import Path

AKAR = Path(__file__).resolve().parent.parent
SUMBER = [
    AKAR / 'public/css/theme.css',
    AKAR / 'public/css/sidebar-nav.css',
    AKAR / 'app/Views/budget_dept/_kepala.php',   # Tom Select & flatpickr modul Budget
]
HASIL = AKAR / 'public/css/skin-executive-peta.css'

# Oranye Neo → navy/emas WBL (lihat token di skin-executive.css).
PETA = [
    (re.compile(r'rgba\(\s*249\s*,\s*115\s*,\s*22\s*,', re.I), 'rgba(31, 78, 140,'),
    (re.compile(r'rgba\(\s*234\s*,\s*88\s*,\s*12\s*,', re.I), 'rgba(10, 42, 110,'),
    (re.compile(r'rgba\(\s*120\s*,\s*70\s*,\s*20\s*,', re.I), 'rgba(10, 42, 110,'),
    (re.compile(r'(?<![\d.])249\s*,\s*115\s*,\s*22(?![\d])'), '31, 78, 140'),   # --bs-primary-rgb dsb.
    (re.compile(r'#f97316\b', re.I), '#1F4E8C'),
    (re.compile(r'#ea580c\b', re.I), '#0A2A6E'),
    (re.compile(r'#c2410c\b', re.I), '#0A2A6E'),
    (re.compile(r'#9a3412\b', re.I), '#0A2A6E'),
    (re.compile(r'#fb923c\b', re.I), '#3D6DB0'),
    (re.compile(r'#fdba74\b', re.I), '#9DB7DC'),
    (re.compile(r'#fed7aa\b', re.I), '#C8D6EC'),
    (re.compile(r'#ffedd5\b', re.I), '#E6EDF7'),
    (re.compile(r'#fff7ed\b', re.I), '#F2F5FA'),
    (re.compile(r'#fffaf3\b', re.I), '#F7F9FC'),
]
ORANYE = re.compile(r'249\s*,\s*115\s*,\s*22|234\s*,\s*88\s*,\s*12|#f97316|#ea580c|#c2410c|#9a3412|#fb923c|#fdba74|#fed7aa|#ffedd5|#fff7ed', re.I)


def css_dari(berkas: Path) -> str:
    teks = berkas.read_text(encoding='utf-8')
    if berkas.suffix == '.php':
        teks = '\n'.join(re.findall(r'<style[^>]*>(.*?)</style>', teks, re.S))
    return re.sub(r'/\*.*?\*/', '', teks, flags=re.S)


def pecah_deklarasi(isi: str):
    # pisah per ';' di luar tanda kurung (gradien berisi koma & titik koma aman)
    hasil, buf, dalam = [], '', 0
    for c in isi:
        if c == '(':
            dalam += 1
        elif c == ')':
            dalam -= 1
        if c == ';' and dalam == 0:
            hasil.append(buf.strip()); buf = ''
        else:
            buf += c
    if buf.strip():
        hasil.append(buf.strip())
    return [d for d in hasil if d]


def ganti_selektor(sel: str) -> str:
    bagian = []
    for s in sel.split(','):
        s = ' '.join(s.split())
        if 'data-theme="light"' not in s:
            continue
        s = s.replace('html[data-theme="light"]', 'html[data-skin="executive"]')
        s = s.replace('[data-theme="light"]', 'html[data-skin="executive"]')
        s = s.replace('html[data-skin="executive"] html[data-skin="executive"]', 'html[data-skin="executive"]')
        bagian.append(s)
    return ',\n'.join(bagian)


def main():
    keluaran = [
        '/* DIBUAT OTOMATIS oleh scripts/petakan_neo_ke_wbl.py — JANGAN diedit tangan.',
        '   Aksen oranye tema Terang (Neo) → navy tema WBL. Dimuat sebelum skin-executive.css. */',
        '',
    ]
    jumlah = 0
    for berkas in SUMBER:
        css = css_dari(berkas)
        keluaran.append(f'/* ── dari {berkas.relative_to(AKAR)} ── */')
        for sel, isi in re.findall(r'([^{}]+)\{([^{}]*)\}', css):
            if 'data-theme="light"' not in sel or not ORANYE.search(isi):
                continue
            selektor = ganti_selektor(sel)
            if not selektor:
                continue
            dekl = []
            for d in pecah_deklarasi(isi):
                # Variabel yang NAMANYA oranye/peringatan sengaja oranye (ikon jam, status) — biarkan.
                nama = d.split(':', 1)[0].strip().lower()
                if 'orange' in nama or 'warning' in nama:
                    continue
                if ORANYE.search(d):
                    for pola, ganti in PETA:
                        d = pola.sub(ganti, d)
                    dekl.append(d)
            if dekl:
                keluaran.append(selektor + ' {\n    ' + ';\n    '.join(dekl) + ';\n}')
                jumlah += 1
        keluaran.append('')
    HASIL.write_text('\n'.join(keluaran), encoding='utf-8')
    sisa = ORANYE.findall(HASIL.read_text(encoding='utf-8'))
    print(f'{jumlah} aturan dipetakan → {HASIL.relative_to(AKAR)}; sisa warna oranye: {len(sisa)}')


if __name__ == '__main__':
    main()
