# Standar animasi MIC

Sumber tunggal: blok `STANDAR ANIMASI MIC` di `app/Views/layouts/main.php`.

| Hal | Standar |
|---|---|
| Masuk halaman | Otomatis untuk semua halaman (tanpa kelas/JS): anak langsung `.page-content`, dan anak langsung wrapper `.page-ev*` kecuali `.ev-head`, fade + naik 8px, **0.35s ease-out**, sekali saat muat. `fill-mode: backwards` (tidak ada `opacity:0` permanen; CSS gagal = konten tetap tampil). |
| `.fade-up`, `.anim-fade-up`, `.anim-fade-in`, `.hr-up` | Warisan; dinetralkan global (tanpa gerak/delay sendiri). Jangan menambah kelas animasi masuk baru; tidak perlu menandai markup. |
| Count-up angka | **Tidak ada** di halaman operasional/laporan (angka langsung utuh). Hanya Dashboard (`dashboard/index.php`, `dashboard-cc.js`, `ispu-widget.js`), maks 0.8 dtk, angka akhir = angka server. |
| Filter / cari / tab / ganti bulan | Tidak menganimasikan ulang tabel atau baris. |
| Hover kartu/tombol/baris | 0.15–0.2s ease. Bar progres (lebar) 0.35s ease. |
| Buka-tutup (accordion/baris detail) | 0.2–0.25s. Modal = Bootstrap bawaan. |
| `prefers-reduced-motion: reduce` | Semua animasi & transisi dimatikan global (spinner tetap berputar); count-up Dashboard dan animasi Chart.js dilewati. |
| Cetak | `@media print` mematikan animasi/transisi. |
| Sidebar | Punya transisi sendiri (`sidebar-nav.css/js`), di luar standar ini. |

Animasi lokal yang boleh tetap: denyut "live", spinner, skeleton shimmer, kilat sorot baris (`is-flash`), kedip umpan balik simpan.
