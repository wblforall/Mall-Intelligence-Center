# TODO — Modul Pest Control MIC

Urutan kerja Fase 1. Alasan dan keputusan ada di
[PESTCARE_DESIGN.md](PESTCARE_DESIGN.md) — dokumen itu menyimpan *fakta dan
alasan*, berkas ini menyimpan *urutan*. Kalau keduanya berbeda, rancangan yang
benar.

Target versi **v2.26.0**. Disiapkan 16 September 2026.

**Titik bisa-dipakai: akhir blok D.** Sesudah itu tim Ops sudah bisa mencatat,
dan blok E–G menambah nilai di atas data yang sudah masuk.

---

## A. Migrasi & skema

- [x] **A1** `app/Database/Migrations/2026-09-16-000001_CreatePestTables.php` —
      lima tabel sesuai §3: `pest_items`, `pest_areas`, `pest_visits`,
      `pest_findings`, `pest_finding_photos`.
      Yang mudah terlewat:
      - `pest_visits`: `KEY(tanggal)` + unique — **kini `UNIQUE(mall, tanggal, sumber)`**
        setelah migrasi `...000002`, lihat temuan 1 di bawah
      - `pest_findings.area_id`: `NOT NULL DEFAULT 0`, **bukan** nullable
      - `pest_findings`: `UNIQUE(visit_id, item_id, area_id)`
      - FK `ON DELETE CASCADE` di `pest_findings` dan `pest_finding_photos`
      - `pest_visits.sumber`: ENUM `('kunjungan','rekap_legacy')` default `'kunjungan'`
- [x] **A2** Seed `pest_items` 8 baris, berurutan: Tikus, Kucing, Biawak, Kecoa,
      Lalat, Ular, **Kelelawar**, **Kupu-kupu**.
      Ejaan diperbaiki dari Excel ("Kekelawar", "Kupu - Kupu").
- [x] **A3** `php spark migrate`, lalu periksa `DESCRIBE` kelima tabel — pastikan
      `area_id` benar-benar `NOT NULL DEFAULT 0`.
- [x] **A4** `mkdir public/uploads/pest` + izin tulis.
      ⚠️ **777, bukan 755.** Apache berjalan sebagai user lain (`daemon`) dari
      pemilik berkas, jadi 755 membuat `mkdir` subfolder per-kunjungan gagal dan
      unggah foto menolak dengan 500. Sama dengan `uploads/vm` & `uploads/work_report`
      yang memang sudah 777.

## B. Model

- [x] **B1** `app/Models/` — `PestItemModel`, `PestAreaModel`, `PestVisitModel`,
      `PestFindingModel`, `PestFindingPhotoModel`. Satu model per tabel utama
      (konvensi MIC).
- [x] **B2** `PestFindingModel::upsertCell()` — pola `DailyTrafficModel::upsertCell()`.
      **Jumlah 0 atau kosong → baris dihapus**, bukan disimpan bernilai 0.
- [x] **B3** `PestVisitModel` — query agregat mingguan (`YEARWEEK(tanggal,1)`) dan
      bulanan (`DATE_FORMAT(tanggal,'%Y-%m')`). Semua agregasi lewat sini, jangan
      query langsung di controller.

## C. Menu, rute, izin

- [x] **C1** `app/Libraries/SectionConfig.php` — tambah `'pest_control' => 'Pest Control'`
      ke `STANDALONE_MENUS`. **Sumber tunggal** — jangan hardcode di tempat lain.
- [x] **C2** `app/Config/Routes.php` — rute sesuai §4, semua `['filter' => 'auth']`
      kecuali master (`auth:admin`).
- [x] **C3** `app/Views/layouts/main.php` — entri sidebar, pola blok traffic.
- [x] **C4** `app/Controllers/PestCtrl.php` — `canViewMenu('pest_control')` /
      `canEditMenu('pest_control')` di tiap method, redirect ke `/events` bila false.
- [x] **C5** Beri izin dept **Operational & Building Maintenance**:
      `can_view=1, can_edit=1` di `department_menu_access`.
      ⚠️ Id dept diperiksa di lokal (= 4) — **pastikan ulang di produksi**.
      ⚠️ `INPUT_ONLY_MENUS` **tidak** disentuh (§4.2).
- [x] **C6** Halaman master item `/pest-items` (`auth:admin`) — nonaktifkan, jangan
      sediakan tombol hapus untuk item yang sudah punya temuan.

## D. Form input — sesudah ini sudah bisa dipakai

- [x] **D1** `PestCtrl::form($mall, $tanggal)` + `app/Views/pest/form.php` —
      8 baris item, pemilih tanggal, total berjalan.
      **Wajib ada tata letak kartu untuk mobile** (pola `traffic/form.php`);
      input datang dari ponsel di lapangan, bukan pelengkap.
- [x] **D2** `PestCtrl::saveCell()` — upsert per baris via AJAX, pola
      `Traffic::saveCell()`. Baris terisi terkunci, dibuka lewat tombol pensil.
- [x] **D3** ⚠️ **Sinkronkan CSRF**: kembalikan `csrf_hash()` di tiap respons JSON,
      lalu tulis ulang **semua** `input[name=mic_csrf_token]` di halaman.
      Jangan `navigator.sendBeacon` — pakai `fetch(..., {keepalive:true})`.
- [x] **D4** Tombol **"Nihil temuan"** → `POST /pest/nihil`, membuat `pest_visits`
      tanpa satu pun `pest_findings`. **Jangan dilewat** — tanpa ini "diperiksa,
      bersih" tidak bisa dibedakan dari "belum diinput" (§4.1).
- [x] **D5** Unggah foto — maks 5/temuan, 10 MB, `MIME_IMAGE`.
      Urutannya: validasi **semua** dulu → simpan ke disk → baru tulis DB.
      `ImageCompressor::normalizeUpload()` sesudah `move()`.
      Gagal di tengah → `unlink` yang terlanjur tersimpan, **nol** baris DB.
- [x] **D6** Hapus foto + hapus kunjungan — transaksi
      (`transStart`/`transComplete`), **berkas fisik dihapus setelah commit**.
      Perhatikan: urutannya kebalikan dari D5, dan keduanya benar (§3.5).
- [x] **D7** `ActivityLog::write()` di setiap create/update/delete.

## E. Tampilan baca

- [x] **E1** `/pest` — tren mingguan, default 12 minggu terakhir, filter mall.
      `YEARWEEK(tanggal, 1)`, minggu **penuh** (tidak dipotong), label
      `W38 · 14–20 Sep 2026`. Kecualikan `sumber='rekap_legacy'`.
- [x] **E2** `/pest/kunjungan` — daftar kunjungan: tanggal, mall, jumlah temuan,
      ada foto/tidak. Tandai yang nihil supaya terlihat beda dari yang kosong.
- [x] **E3** `/pest/summary` — rekap bulanan & tahunan + pembanding antar tahun.
      Di sini `rekap_legacy` **ikut**.
- [x] **E4** Grafik Chart.js — `animation:false`, palet CVD-safe.

## F. Impor data legacy

Tidak bergantung pada blok C–E. Bisa dikerjakan kapan saja setelah A.

- [x] **F1** Importer dari dua xlsx di `~/Downloads` → 21 bulan × 2 mall =
      **42 baris** `pest_visits`, `sumber='rekap_legacy'`, `tanggal` = hari
      terakhir bulan, temuan `area_id=0`, **hanya untuk nilai bukan nol**.
- [x] **F2** ⚠️ Angka di Excel tersimpan sebagai **teks** — cast, jangan
      mengandalkan tipe aslinya.
- [x] **F3** Verifikasi: total per item per tahun harus sama persis dengan kolom
      TOTAL di Excel. Patokan yang sudah dihitung ulang dan cocok:

      | | eWalk 2025 | Penta 2025 | eWalk 2026 | Penta 2026 |
      |---|---|---|---|---|
      | Tikus | 496 | 633 | 297 | 450 |
      | Kecoa | 941 | 717 | 305 | 338 |

## G. Laporan bulanan cetak

- [x] **G1** `/pest/laporan-bulanan?bulan=YYYY-MM` — view standalone A4 landscape
      **font 11px**, pakai `app/Views/_laporan/_style.php` + `_ttd.php`.
- [x] **G2** ⚠️ Rincian mingguan **dipotong di batas bulan**, minggu potongan
      ditandai "sebagian" — supaya baris mingguannya berjumlah sama dengan total
      bulan (§2.2). Ini beda perlakuan dari E1, dan sengaja.
- [x] **G3** `ReportSignatories::resolve('pest_control')` — tidak ada kode
      tambahan, terisi sendiri begitu C5 selesai.
- [x] **G4** Susunan isi baku: KPI (delta vs bulan lalu) → rekap per mall →
      Ringkasan Analisa + 2 grafik → tabel detail → ttd.

## Hasil pengujian 16 Sep 2026, diperiksa ulang & ditinjau tampilannya 19 Sep (lokal)

Diuji lewat peramban sebagai pengguna dept Ops sungguhan, bukan sekadar
memeriksa kode. **Lima temuan, kelimanya sudah diperbaiki:**

1. **`UNIQUE(mall, tanggal)` memblokir kunjungan pada tanggal yang dipakai baris
   impor.** Baris impor mewakili satu bulan, kunjungan mewakili satu hari —
   keduanya berebut satu tanggal dan pengguna menemui jalan buntu. Diperbaiki:
   kunci jadi `UNIQUE(mall, tanggal, sumber)` (migrasi `...000002`), dan
   `getByMallTanggal()` menyaring `sumber='kunjungan'` agar form tidak pernah
   memungut baris impor.
2. **Impor bulan berjalan melahirkan tanggal masa depan** (30 Sep padahal hari
   ini 16 Sep). Diperbaiki: tanggal dibatasi `min(akhir bulan, hari ini)`.
3. **Laporan menyatakan dua hal yang tidak benar saat ada data impor** —
   subjudul mengklaim "jumlah mingguan sama dengan total bulan" (2 ≠ 41), dan
   ringkasan menulis "41 temuan dari 2 kunjungan" padahal 39 di antaranya dari
   rekap impor. Keduanya kini berbunyi berbeda bila `jmlLegacy > 0`.

4. **Importer tidak idempoten lintas hari** — *ditemukan saat pemeriksaan ulang
   19 Sep.* Tanggal baris bulan berjalan dibatasi ke "hari ini", jadi impor yang
   dijalankan di hari berbeda menghasilkan tanggal berbeda untuk bulan yang
   sama; pemeriksaan "sudah terimpor" yang membandingkan **tanggal** jadi meleset
   dan bulan berjalan terimpor ulang — angkanya berlipat diam-diam.
   `UNIQUE(mall, tanggal, sumber)` tidak mencegahnya karena tanggalnya memang
   beda. Diperbaiki: pemeriksaan dilakukan **per bulan**, bukan per tanggal.
   Terbukti: dijalankan ulang → 0 dibuat, 42 dilewati, jumlah tetap 42/120/4.414.

5. **Label tombol filter yang sedang aktif tidak terbaca** — *ditemukan saat
   meninjau tampilan.* `theme.css` menimpa `.btn-outline-*` dan `.btn-secondary`
   dengan `!important`, sehingga aturan `.active` milik Bootstrap kalah: tombol
   aktif jadi abu-abu di atas abu-abu (praktis tak terlihat), dan varian primary
   jadi oranye di atas biru. Ini berlaku **global di MIC**, bukan khusus modul
   ini — halaman lama menyiasatinya dengan menukar kelas, bukan menambah
   `.active` (lihat `app/Views/traffic/summary.php`). Diperbaiki dengan mengikuti
   pola itu: aktif = `btn-primary` (gradien, teks putih), nonaktif =
   `btn-outline-secondary`. **`theme.css` tidak disentuh**, jadi tidak perlu
   menaikkan `?v=` dan modul lain tidak terpengaruh.

**Yang terbukti bekerja end-to-end:** simpan per baris; rotasi CSRF pada POST
berturut-turut tanpa reload; kunci baris + tombol Ubah; unggah 2 foto sekaligus;
kosongkan jadi 0 → baris, foto di DB, dan **berkas fisik** ikut terhapus;
tombol Nihil temuan (kunjungan ada, temuan nol); hapus kunjungan → transaksi,
berkas & folder bersih, **42 baris impor tidak tersentuh**; master item menolak
hapus item yang sudah punya temuan; `/pest-items` menolak non-admin; tanda
tangan laporan terisi sendiri (Musfiandi Taqwin / Christian Y.r. Pangkerego /
Alfialdy) tanpa kode tambahan.

**Angka terverifikasi penuh** (bukan sampel) — perbandingan sel demi sel
terhadap kedua berkas Excel: **120 sel, nol selisih**, nol yang hilang, nol yang
ekstra. Total 4.414 di kedua sisi. Rekap tahunan Tikus 2026 = 747, 2025 = 1.129,
selisih −382.

**Catatan lingkungan:** `composer test` gagal 5/5 dengan `Class "Locale" not
found` — ekstensi `intl` tidak terpasang di PHP XAMPP. Diperiksa dengan
`git stash`: **kegagalan ini sudah ada sebelum modul pest**, bukan akibatnya.
`php spark routes` juga gagal karena rute `penautan-akun` yang sudah ada.

---

## H. Uji & rilis

- [ ] **H1** ⚠️ **Uji unggah dari iPhone sungguhan** — HEIC tidak ada di
      `MIME_IMAGE`. Kalau lolos HEIC, tambahkan mime + konversi, atau tolak dengan
      pesan jelas. Jangan diasumsikan aman.
- [x] **H2** Uji CSRF: isi beberapa baris berturut-turut tanpa reload, lalu unggah
      foto. Kalau token basi, submit ditolak **diam-diam**.
- [x] **H3** Uji `UNIQUE(mall, tanggal)` — buka form tanggal yang sama dua kali,
      pastikan tidak lahir kunjungan ganda.
- [ ] **H4** ⚠️ **Verifikasi di produksi, bukan lokal**: id dept Operational &
      Building Maintenance, dan isi blok Disusun/Diperiksa/Mengetahui di laporan.
      Angka di rancangan dibaca dari basis data lokal.
- [ ] **H5** `php spark migrate` di produksi + seed izin C5.
- [ ] **H6** Uji satu URL sungguhan setelah deploy — **uji perilakunya, bukan
      penandanya**. `git log`, berkas di disk, dan `route:list` bisa benar semua
      sementara yang dilayani ke pengguna tetap 404.
- [ ] **H7** Rilis: pakai skill `/finishing` (bump versi, RELEASE_NOTE.md, commit,
      push).

---

## Fase 2 — JANGAN dikerjakan sekarang

Ditunda karena **prasyaratnya data, bukan kode**. Dikerjakan sebelum prasyaratnya
ada, keduanya akan salah bentuk.

- [ ] Master area + rincian area di form + titik rawan di laporan
      — *menunggu daftar titik dari tim Ops*
- [ ] Notifikasi ambang lonjakan
      — *menunggu ±3 bulan data mingguan; angka ±25–30/mall/minggu yang pernah
      disebut itu tebakan dari rekap bulanan, bukan standar*

Skema Fase 1 sudah menampung keduanya, jadi **tidak ada migrasi ulang**.
