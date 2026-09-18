# Rancangan Modul Pest Control — MIC

Status: **Fase 1 terbangun & teruji di lokal** (16 Sep 2026, diperiksa ulang 19 Sep)  
Target versi: v2.26.0  
Disusun: 16 September 2026

> **Lingkup Fase 1.** Rincian per area **tidak dibangun dulu** — tabel
> `pest_areas` dan kolom `area_id` tetap dibuat sesuai rancangan ini, tapi
> antarmukanya menyusul di Fase 2. Seluruh temuan Fase 1 tercatat dengan
> `area_id = 0` ("tidak dirinci"). Karena skemanya sudah menampung area sejak
> awal, Fase 2 **tidak perlu migrasi ulang** — hanya menambah antarmuka.
> Bagian bertanda **(Fase 2)** di dokumen ini belum dikerjakan.

---

## 1. Latar

Pencatatan temuan pest selama ini berupa dua berkas Excel rekap **bulanan** —
`Rekapan Temuan PestCare eWalk Penta 2025.xlsx` dan `…2026.xlsx` — masing-masing
dua tabel (eWalk, Pentacity), baris = item temuan, kolom = Januari–Desember.

Yang diminta sekarang: pencatatan **per minggu**, dengan **lokasi/area temuan**
dan **foto bukti**.

Fakta dari berkas lama yang ikut membentuk rancangan ini:

- **Daftar item berubah antar tahun.** 2025 punya 6 item (Tikus, Kucing, Biawak,
  Kecoa, Lalat, Ular); 2026 menambah Kelelawar dan Kupu-kupu. Karena itu daftar
  item **wajib jadi tabel master**, bukan ENUM di kolom.
- Rekap bulanan adalah keluaran yang selama ini dipakai dan **harus tetap benar**
  setelah pindah ke mingguan.
- Data 2026 baru terisi Januari–September; Oktober–Desember masih kosong.

---

## 2. Keputusan inti: yang disimpan adalah **kunjungan bertanggal**, bukan minggu

Persoalannya sudah ditemukan sejak awal: **tidak semua bulan berisi 4 minggu.**
Kalau tabelnya punya kolom `minggu_ke` (1–5), kalender dipaksa masuk ke slot yang
tidak pas, dan dua hal langsung rusak:

- Minggu bisa membelah bulan — Senin 28 Sep s.d. Minggu 4 Okt itu satu minggu,
  dua bulan. Temuan tanggal 2 Oktober masuk ke mana?
- Rekap bulanan jadi butuh tafsiran, padahal itu keluaran yang harus tetap benar.

**Jalan keluarnya: satuan simpan adalah satu kunjungan pada satu tanggal.**
Nomor minggu tidak pernah disimpan — ia **dihitung saat menampilkan**.

Konsekuensinya, pertanyaan "4 atau 5 minggu" tidak pernah perlu dijawab di tingkat
basis data. Tidak ada slot yang disediakan di muka; bulan yang punya 5 minggu
memunculkan 5 baris, yang 4 minggu memunculkan 4.

Pola ini bukan hal baru di MIC — `daily_traffic` juga menyimpan `tanggal` dan
memetakannya ke event/bulan saat query, persis dengan alasan yang sama.

### 2.1 Penomoran minggu: ISO Senin–Minggu

Minggu memakai **ISO-8601** (Senin awal minggu, minggu 1 = minggu yang memuat
≥4 hari Januari), lewat `YEARWEEK(tanggal, 1)`. Ini sudah dipakai di MIC pada
`WorkReportCtrl` (`date('oW')`) dan `WorkInitiativeModel` — jadi satu definisi
minggu untuk seluruh aplikasi, bukan dua.

Tampilan: `W38 · 14–20 Sep 2026`.

### 2.2 Akibat yang harus disadari, bukan disembunyikan

Minggu ISO **membelah bulan** di perbatasan. Karena itu ada dua tampilan dengan
perlakuan berbeda, masing-masing konsisten di dalam dirinya sendiri:

| Tampilan | Satuan | Perlakuan minggu perbatasan |
|---|---|---|
| **Tren mingguan** (`/pest`) | minggu ISO penuh | utuh 7 hari, boleh lintas bulan — inilah tren yang jujur |
| **Rekap & laporan bulanan** | bulan kalender | minggu perbatasan **dipotong** di batas bulan, ditandai "sebagian" |

Dengan pemotongan itu, **jumlah baris mingguan di laporan bulanan selalu sama
dengan total bulannya.** Di halaman tren, angka mingguan utuh — dan di sana
memang tidak ada total bulanan yang perlu dicocokkan.

Yang tidak boleh dilakukan: menaruh minggu ISO penuh dan total bulanan di satu
tabel yang sama tanpa penanda. Itu melahirkan dua angka yang beda dan dua-duanya
terlihat benar.

> Total bulanan sendiri **selalu tepat** apa pun definisi minggunya, karena ia
> dihitung dari `MONTH(tanggal)` tiap kunjungan — dan satu kunjungan tidak pernah
> membelah bulan. Hanya *ember mingguan* yang membelah, bukan datanya.

---

## 3. Skema basis data

Lima tabel. Semua berdiri sendiri, tidak terikat `event_id` — sejajar dengan
`daily_traffic` / `daily_vehicles`.

### 3.1 `pest_items` — master item temuan

| Kolom | Tipe | Ket |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `nama` | VARCHAR(60) | Tikus, Kucing, … |
| `urutan` | SMALLINT UNSIGNED | urutan tampil |
| `aktif` | TINYINT(1) default 1 | item lama dinonaktifkan, **tidak dihapus** |
| `created_at` | DATETIME | |

Seed: Tikus, Kucing, Biawak, Kecoa, Lalat, Ular, Kelelawar, Kupu-kupu.

Ejaan diperbaiki dari berkas sumber: **Kelelawar** (di Excel 2026 tertulis
"Kekelawar"), **Kupu-kupu** (di Excel "Kupu - Kupu").

Item dinonaktifkan, bukan dihapus — menghapus item yang sudah punya temuan akan
membuat angka historis hilang tanpa jejak.

### 3.2 `pest_areas` — master lokasi/area, per mall

| Kolom | Tipe | Ket |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `mall` | ENUM('ewalk','pentacity') | area tidak berlaku lintas mall |
| `nama` | VARCHAR(100) | mis. "Food Court", "Basement P1", "Toilet Lt.2" |
| `urutan` | SMALLINT UNSIGNED | |
| `aktif` | TINYINT(1) default 1 | |

Tidak di-seed — diisi admin sesuai denah masing-masing mall.

**(Fase 2)** Tabelnya dibuat di Fase 1 supaya `area_id` punya rujukan yang sah
dan Fase 2 tidak menuntut migrasi ulang, tapi isinya masih kosong dan halaman
masternya belum dibangun.

### 3.3 `pest_visits` — kunjungan

| Kolom | Tipe | Ket |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `mall` | ENUM('ewalk','pentacity') | |
| `tanggal` | DATE | **satu-satunya penanda waktu** |
| `vendor` | VARCHAR(80) NULL | default "PestCare" |
| `petugas` | VARCHAR(100) NULL | |
| `sumber` | ENUM('kunjungan','rekap_legacy') default 'kunjungan' | lihat §6 |
| `catatan` | TEXT NULL | |
| `created_by` | INT UNSIGNED NULL | |
| `created_at` / `updated_at` | DATETIME NULL | |

- `UNIQUE(mall, tanggal, sumber)` — **satu kunjungan per mall per hari**, tetap
  seperti yang diputuskan, tapi ditegakkan dalam lingkup tiap `sumber`.

  > **Dikoreksi setelah pengujian.** Rancangan semula memakai
  > `UNIQUE(mall, tanggal)`. Ternyata itu membuat baris impor — yang mewakili
  > satu BULAN dan diberi tanggal hari terakhir bulan itu — berebut tanggal
  > dengan kunjungan sungguhan, sehingga kunjungan pada tanggal tersebut tidak
  > bisa dicatat sama sekali dan pengguna hanya melihat pesan "tidak dapat
  > disunting" tanpa jalan keluar. Keduanya jenis catatan berbeda, jadi
  > kuncinya yang harus membedakan. Migrasi `2026-09-16-000002`.

  Arahnya tetap yang mudah: melonggarkan jadi index biasa bila ternyata ada
  sesi pagi/sore, sedangkan memperketat setelah baris ganda masuk menuntut
  pembersihan data.
- `KEY(tanggal)` — untuk agregasi mingguan/bulanan lintas mall.

### 3.4 `pest_findings` — temuan

| Kolom | Tipe | Ket |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `visit_id` | INT UNSIGNED FK → `pest_visits` | ON DELETE CASCADE |
| `item_id` | INT UNSIGNED FK → `pest_items` | |
| `area_id` | INT UNSIGNED NOT NULL default 0 | **0 = tidak dirinci** |
| `jumlah` | INT UNSIGNED default 0 | |
| `created_at` | DATETIME NULL | |

`UNIQUE(visit_id, item_id, area_id)`.

Catatan soal `area_id = 0`: kolomnya sengaja `NOT NULL DEFAULT 0`, bukan
nullable. MySQL mengizinkan banyak NULL di dalam satu unique key, jadi kalau
`area_id` boleh NULL, uniknya tidak menggigit untuk temuan yang tidak dirinci
lokasinya — dan baris ganda bisa masuk diam-diam. Nilai sentinel 0 membuat
aturannya tetap ditegakkan basis data, bukan cuma oleh aplikasi.

Temuan tanpa area tetap sah. Lapangan tidak selalu merinci lokasi, dan menolak
kiriman karena kolom area kosong hanya akan melahirkan area sampah bernama "-".

### 3.5 `pest_finding_photos` — foto bukti

| Kolom | Tipe | Ket |
|---|---|---|
| `id` | INT UNSIGNED PK AI | |
| `finding_id` | INT UNSIGNED FK → `pest_findings` | ON DELETE CASCADE |
| `file_name` | VARCHAR(255) | nama acak di disk |
| `original_name` | VARCHAR(255) NULL | nama asli dari perangkat |
| `created_at` | DATETIME NULL | |

**Tabel anak terpisah, jadi satu temuan boleh punya banyak foto** — itu memang
sebabnya ia tidak dibuat sebagai kolom di `pest_findings`.

Foto menempel di **temuan**, bukan di kunjungan — supaya nanti "foto tikus di
food court" tetap tahu tikusnya dan food court-nya. Di Fase 1 ia menempel di
baris `area_id = 0`, dan tetap berfungsi penuh.

Berkas fisik di `public/uploads/pest/{visit_id}/`, mengikuti pola
`uploads/work_report/{id}/`.

#### Aturan unggah

Mengikuti pola yang sudah berjalan di `WorkReportCtrl::addUpdate()`:

| Hal | Nilai | Sebab |
|---|---|---|
| Maksimal per temuan | **5 foto** | sama dengan work report |
| Ukuran per berkas | **10 MB** (default `validateUpload`) | foto langsung dari kamera ponsel kerap >5 MB; `ImageCompressor` mengecilkannya setelah tersimpan |
| Jenis | `BaseController::MIME_IMAGE` — jpeg, png, webp, gif | |
| Nama berkas | `pest_{time}_{random_bytes(8)}.{safeExt}` | nama asli tidak pernah dipakai di disk |
| Pascaunggah | `ImageCompressor::normalizeUpload()` | PNG/WebP → JPEG, resize maks 1600px, kompresi |

Diunggah lewat `getFileMultiple()`, disaring dari `UPLOAD_ERR_NO_FILE`, dan
**seluruhnya divalidasi lebih dulu sebelum satu pun disimpan** — menolak di
tengah jalan menyisakan sebagian foto terunggah dan sebagian tidak.

#### ⚠️ Urutan tulis: berkas dulu, baru basis data

Ini kebalikan dari aturan hapus, dan keduanya benar:

- **Unggah** — berkas disimpan ke disk **sebelum** ada tulisan ke basis data.
  Kalau `mkdir`/`move` gagal setelah baris DB dibuat, yang tersisa adalah temuan
  tanpa foto plus error 500 — dan itu memancing pengguna mengirim ulang, lahir
  temuan ganda. Bila ada yang gagal di tengah, berkas yang terlanjur tersimpan
  di-`unlink` dan tidak satu pun baris DB dibuat.
- **Hapus** — berkas fisik dihapus **hanya setelah** `transComplete()` berhasil.
  Kalau transaksi gagal setelah berkas hilang, barisnya masih ada tapi fotonya
  lenyap permanen.

Aturannya satu: **yang tidak bisa dibatalkan dikerjakan belakangan.** Saat
menulis, berkas lebih mudah dihapus daripada baris DB ditarik; saat menghapus,
baris DB bisa di-rollback sedangkan berkas tidak bisa dikembalikan.

#### Yang perlu diperiksa saat implementasi

**iPhone bisa mengirim HEIC**, dan `MIME_IMAGE` tidak memuatnya. Peramban iOS
umumnya mengubah ke JPEG saat unggah lewat form, tapi tidak selalu — dan modul
ini justru paling sering dipakai dari ponsel di lapangan. Perlu diuji dengan
iPhone sungguhan sebelum rilis; kalau lolos HEIC, pilihannya menambah
`image/heic` ke daftar mime plus konversi di `ImageCompressor`, atau menolak
dengan pesan yang jelas.

### 3.6 Bentuk relasi

```
pest_items ──┐
             ├─< pest_findings >─── pest_visits (mall, tanggal)
pest_areas ──┘        │
                      └─< pest_finding_photos
```

Butir data terkecil: **(kunjungan, item, area) → jumlah**.
Rekap bulanan lama = agregat butir ini per `MONTH(tanggal)`, per mall, per item.

---

## 4. Halaman & rute

Menu key baru: **`pest_control`** — ditambahkan ke
`SectionConfig::STANDALONE_MENUS` (label: "Pest Control"). Itu sumber tunggal
daftar menu; sidebar dan halaman akses departemen ikut otomatis, jangan
di-hardcode ulang.

| Rute | Isi |
|---|---|
| `GET /pest` | **Tren mingguan.** Default 12 minggu terakhir, filter mall. Tabel item × minggu ISO + grafik tren. |
| `GET /pest/kunjungan` | Daftar kunjungan (tanggal, mall, jumlah temuan, ada foto/tidak). |
| `GET /pest/input/(:alpha)/(:any)` | Form satu kunjungan, bentuk **hibrida** (§4.1): baris item + rincian area opsional + foto. |
| `POST /pest/save-cell` | Simpan satu baris temuan inline (upsert), pola `Traffic::saveCell`. |
| `POST /pest/nihil` | Catat kunjungan tanpa temuan (§4.1). |
| `POST /pest/foto` | Unggah foto bukti (banyak sekaligus) ke satu temuan. |
| `POST /pest/foto/(:num)/hapus` | Hapus satu foto. |
| `GET /pest/foto-list/(:num)` | Daftar foto satu temuan (JSON, untuk menggambar ulang strip). |
| `POST /pest/delete/(:num)` | Hapus kunjungan beserta temuan & foto. |
| `GET /pest/summary` | Rekap bulanan & tahunan, pembanding **tahun ini vs tahun lalu**. |
| `GET /pest/laporan-bulanan` | `?bulan=YYYY-MM` — cetak A4, pola laporan baku. |
| `GET /pest-items` | Master item temuan, `auth:admin`. |
| `GET /pest-areas` | Master area — **(Fase 2, belum dibuat)**, `auth:admin`. |

Semua rute non-admin pakai `['filter' => 'auth']`, dan controller memanggil
`canViewMenu('pest_control')` / `canEditMenu('pest_control')` di awal — kalau
false, redirect ke `/events`.

### 4.1 Form input kunjungan

Mekanismenya meniru `Traffic::form` + `Traffic::saveCell`, **bentuk tabelnya tidak.**

Yang diambil utuh dari traffic:

- Satu halaman per `(mall, tanggal)`, berpindah lewat pemilih tanggal.
- **Simpan per baris via AJAX** (`pest/save-cell`, upsert) — bukan satu form
  besar. Aman untuk input paralel, dan tidak pernah menghapus baris lain.
- Baris terisi **terkunci**, dibuka lewat tombol pensil "Ubah"
  (`canEditFilled`).
- Total berjalan, diperbarui langsung tanpa reload.
- **Tata letak kartu di mobile** — input pest kemungkinan besar dari ponsel di
  lapangan, jadi ini bukan pelengkap.
- Rotasi `csrf_hash()` di tiap respons JSON, lalu tulis ulang seluruh
  `input[name=mic_csrf_token]` di halaman.

Yang **tidak** ditiru, beserta alasannya:

| Traffic | Pest | Sebab |
|---|---|---|
| Grid padat jam × pintu, dimaksudkan terisi penuh | 8 baris item, mayoritas nol | Satu kunjungan biasanya hanya 3–5 angka bukan nol. Grid item × area (8 × 15 = 120 sel) akan memaksa mengisi ratusan sel kosong untuk 4 angka. |
| Batas 3 hari (`INPUT_WINDOW_DAYS`) | tidak ada batas | Batas itu hanya berlaku bagi pengguna tanpa `can_view`. Tim Ops punya `can_view`, jadi jatuh di sisi yang bebas — lihat §4.2. |
| Sel kosong = belum diinput | Nol = nihil, dan itu data yang sah | Diselesaikan di tingkat kunjungan, bukan sel — lihat di bawah. |
| Sel hanya menampung angka | Temuan menampung foto | Foto bukti tidak punya tempat di sel grid. |

#### Bentuk Fase 1

Delapan baris item tetap — sama seperti Excel, jadi tidak ada yang perlu
dipelajari ulang. Isi jumlahnya, lampirkan foto bila ada.

```
eWalk · Kamis, 17 Sep 2026       [◀] [tanggal] [▶]

        [ ✓ Nihil temuan ]

Item         Jumlah   Foto
──────────────────────────────────────────────
Tikus         [ 5]    [📷 2]  [+ foto]
Kucing        [ 1]    [ — ]   [+ foto]
Biawak        [ 0]
Kecoa         [ 0]
Lalat         [ 0]
Ular          [ 0]
Kelelawar     [ 0]
Kupu-kupu     [ 0]
              ────
TOTAL            6
```

Tiap baris = satu `pest_findings` dengan `area_id = 0`.

#### Bentuk Fase 2 *(belum dikerjakan)*

Baris yang ada temuannya bisa dibuka untuk merinci per area:

```
Tikus           5    ▾ Food Court   3  [📷 2]
                       Basement P1   2  [📷 1]
                       [+ area]
Kucing        [ 1]   ▸ tidak dirinci    [+ area]
```

**Satu angka, satu sumber.** Per item berlaku salah satu, tidak pernah keduanya:

- *Tidak dirinci* — satu baris dengan `area_id = 0`, jumlah diketik langsung.
  **Ini satu-satunya bentuk yang ada di Fase 1.**
- *Dirinci* — beberapa baris dengan `area_id > 0`; jumlah item menjadi
  **hasil penjumlahan area, dan tidak bisa diketik** (read-only di form).

Begitu area pertama ditambahkan, baris `area_id = 0` **dipindahkan** ke area itu,
bukan dibiarkan berdampingan. Tanpa aturan ini "Tikus 5" dan "Food Court 3 +
Basement 2" bisa berbeda diam-diam, dan dua-duanya terlihat sahih. Aturannya
ditegakkan di form **dan** di server — `UNIQUE(visit_id, item_id, area_id)` saja
tidak mencegah baris `area_id = 0` hidup berdampingan dengan baris area.

Aturan ini ditulis sekarang walaupun belum dipakai, karena ia menentukan cara
data Fase 1 ditafsirkan saat Fase 2 datang: temuan lama yang `area_id = 0` tetap
sah dan tidak perlu disentuh.

**Nol tidak disimpan.** Jumlah dikosongkan atau diisi 0 → barisnya dihapus,
bukan disimpan bernilai 0. Tabel `pest_findings` tetap jarang dan rekap tidak
perlu menyaring nol.

**Kunjungan nihil tetap kunjungan.** Karena nol tidak disimpan, keberadaan baris
`pest_visits` lah yang membuktikan pemeriksaan dilakukan — item tanpa baris
temuan berarti nihil, bukan belum diinput. Maka form wajib punya tombol
**"Nihil temuan"** yang membuat kunjungan tanpa satu pun temuan.

Ini data yang selama ini hilang di Excel: bulan bernilai 0 di sana tidak bisa
dibedakan antara "diperiksa, bersih" dan "belum sempat diinput". Kecoa eWalk
2026 punya lima bulan bernilai 0 — sampai sekarang tidak ada cara tahu yang mana.

**Foto menempel di baris temuan mana pun**, termasuk baris `area_id = 0` — tidak
menuntut area dirinci lebih dulu. Karena itu foto bukti sudah berfungsi penuh di
Fase 1 meski areanya belum ada.

### 4.2 Pemilik modul: tim Ops

Input dikerjakan **tim Ops internal**, bukan vendor PestCare.

Departemennya: **Operational & Building Maintenance** — non-outsource, divisi
*Operasional & Building Maintenance*. Departemen yang sama sudah memegang
`traffic` dengan `can_view=1, can_edit=1`, jadi `pest_control` diberi bentuk
akses yang persis sama.

> Diperiksa di **basis data lokal**, bukan produksi. Master departemen jarang
> berubah, tapi id dan susunan jabatannya tetap perlu dipastikan di produksi
> sebelum seed izin dijalankan.

Dua penyederhanaan yang langsung mengikut dari keputusan ini:

**Jalur "input tanpa lihat" tidak diperlukan.** `INPUT_ONLY_MENUS` di
`app/Views/departments/edit.php` **tidak disentuh**. Jalur itu ada untuk kasus
Security — departemen outsource yang menginput traffic tanpa boleh melihat
rekapnya. Tim Ops internal memegang `can_view` juga, jadi kasusnya tidak muncul.

**Jendela input tidak perlu dibatasi.** Di traffic, batas 3 hari
(`INPUT_WINDOW_DAYS`) hanya berlaku bagi pengguna tanpa `can_view` — supervisor
dan admin bebas. Karena tim Ops punya `can_view`, mereka jatuh di sisi yang
bebas. Ini sekaligus menutup pertanyaan §9 soal panjang jendela input: tidak ada
jendela yang perlu ditentukan.

Tim Ops juga pemilik gedungnya, jadi merekalah yang tepat mengisi master
`pest_areas` — pertanyaan daftar area (§9.1) punya alamat yang jelas sekarang.

---

## 5. Laporan bulanan

Mengikuti pola baku lima modul yang sudah ada (Loyalty, Sponsorship, Traffic,
Parkir Pendapatan, Parkir Kendaraan):

- View standalone **A4 landscape, font 11px**, memakai `app/Views/_laporan/_style.php`
  dan `_laporan/_ttd.php`.
- Tanda tangan lewat `ReportSignatories::resolve('pest_control')` — departemen
  penyusun = **dept pemilik modul**, bukan dept si pencetak. **Tidak ada kode
  tambahan**: begitu `pest_control` terdaftar dengan `can_edit` di Operational &
  Building Maintenance, blok tanda tangannya terisi sendiri. Docblock library itu
  bahkan memakai traffic = Operational sebagai contohnya.

  Hasilnya (per data **lokal**, perlu dipastikan di produksi):

  | Blok | Terisi |
  |---|---|
  | Disusun | Musfiandi Taqwin — Manager Operational & Building Maintenance (grade terendah di dept) |
  | Diperiksa | Christian Y.r. Pangkerego — Deputy GM Operational & Building Maintenance |
  | Mengetahui | Alfialdy — General Manager |

  Divisi Operasional & Building Maintenance tidak punya Senior Manager grade 4,
  jadi kolom "Diperiksa" hanya berisi Deputy GM — ditangani library, bukan kasus
  khusus yang perlu ditulis.
- Susunan isi: KPI (delta vs bulan lalu) → rekap per mall → Ringkasan Analisa
  + 2 grafik Chart.js (`animation:false`, palet CVD-safe) → tabel detail → ttd.

Isi khas modul ini:

- **Rincian mingguan dipotong di batas bulan** (§2.2), tiap minggu diberi
  rentang tanggalnya; minggu potongan ditandai "sebagian".
- Rekap per item per mall — inilah kolom yang selama ini ada di Excel, jadi
  laporan ini menggantikannya sepenuhnya.
- **(Fase 2)** Daftar **titik rawan** — area dengan temuan berulang. Ini nilai
  tambah yang tidak mungkin didapat dari Excel lama, dan alasan utama rincian
  area layak dikerjakan di fase berikutnya.

---

## 6. Data historis 2025–2026

Excel lama adalah rekap **bulanan**. Tidak ada tanggal kunjungan di sana, dan
**tidak boleh dikarang** — memasukkannya sebagai kunjungan mingguan palsu akan
melahirkan tren mingguan yang terlihat sahih padahal fiktif.

**Diputuskan: kedua tahun diimpor.** 2025 penuh (12 bulan) dan 2026 Januari–
September, sebagai satu baris `pest_visits` per bulan per mall dengan
`sumber = 'rekap_legacy'`, `tanggal` = hari terakhir bulan itu, temuan
`area_id = 0`.

Jumlahnya: 12 + 9 = 21 bulan × 2 mall = **42 baris kunjungan**, dengan temuan
hanya untuk item yang nilainya bukan nol — **120 baris temuan**, terverifikasi
sama persis dengan kolom TOTAL kedua berkas Excel.

⚠️ **Tanggal tidak pernah melewati hari ini.** Ditemukan saat pengujian: bulan
berjalan (September 2026) mendarat di 30 Sep padahal hari ini baru tanggal 16 —
melahirkan kunjungan bertanggal masa depan. Importer kini memakai
`min(hari terakhir bulan, hari ini)`.

Jalannya lewat `php spark mic:pest-import-legacy` (ada `--dry` untuk melihat
rencana tanpa menulis). **Aman dijalankan ulang** — bulan yang sudah terimpor
dilewati.

⚠️ Pemeriksaan "sudah terimpor" itu **per bulan, bukan per tanggal**. Karena
tanggal bulan berjalan dibatasi ke hari ini, impor yang dijalankan di hari
berbeda menghasilkan tanggal berbeda untuk bulan yang sama — membandingkan
tanggal membuat bulan berjalan terimpor ulang dan angkanya berlipat diam-diam.
`UNIQUE(mall, tanggal, sumber)` tidak menolongnya, karena tanggalnya memang
beda. Ditemukan pada pemeriksaan ulang 19 Sep 2026.

Aturannya:

- **Masuk** ke rekap bulanan, rekap tahunan, dan pembanding tahun-ke-tahun —
  di sana satuannya bulan, dan datanya memang bulanan. Sah.
- **Dikecualikan** dari tren mingguan dan dari laporan yang merinci minggu —
  filter `sumber = 'kunjungan'`. Di sana satuannya minggu, dan datanya tidak
  punya resolusi itu.

Dengan begitu perbandingan 2025 vs 2026 vs 2027 tetap bisa dilakukan tanpa satu
pun angka mingguan yang dikarang.

Angka Excel tersimpan sebagai **teks**, bukan numerik — importer harus meng-cast,
jangan mengandalkan tipe aslinya. Seluruh kolom TOTAL di kedua berkas sudah
diperiksa ulang dan cocok dengan penjumlahan bulanannya.

---

## 7. Hal wajib sesuai konvensi MIC

- **`ActivityLog::write()`** pada setiap create/update/delete.
- **Transaksi** untuk simpan/hapus multi-tabel; hapus berkas fisik setelah commit.
- **Sinkronkan token CSRF** ke seluruh form di halaman pada setiap AJAX POST
  (`Config\Security::$regenerate = true` merotasi token tiap POST). Kembalikan
  `csrf_hash()` di respons JSON lalu tulis ulang semua
  `input[name=mic_csrf_token]`. Ini menggigit di `save-cell` dan di unggah foto.
  Jangan pakai `navigator.sendBeacon`; pakai `fetch(..., {keepalive:true})`.
- **Naikkan `?v=`** pada aset JS/CSS baru tiap kali diubah.
- Grouping/filtering di controller, bukan di view.

### Notifikasi — ditunda (bukan dibatalkan)

**Diputuskan: tidak dibuat sekarang.** Ambang yang masuk akal hanya bisa
ditetapkan dari data mingguan nyata, dan sampai modul ini jalan belum ada satu
pun. Angka yang sempat saya sebut (±25–30 ekor per mall per minggu untuk Tikus)
adalah **tebakan yang diturunkan dari rekap bulanan, bukan standar**.

Notifikasi dengan ambang yang keliru lebih buruk daripada tidak ada: orang
berhenti membacanya, lalu notifikasi modul lain ikut tenggelam bersamanya.

Ditinjau ulang setelah ±3 bulan data mingguan terkumpul. Saat dibuat nanti:
lewat `Notify::send()`, penerima via `OrgRecipients`, dan **wajib dibungkus
`OrgRecipients::orAdmins()`** agar tidak lenyap tanpa jejak.

---

## 8. Urutan kerja

### Fase 1 — dikerjakan sekarang

| # | Langkah | Hasil |
|---|---|---|
| 1 | Migrasi 5 tabel + seed `pest_items` | skema siap, termasuk `pest_areas` yang belum dipakai |
| 2 | `SectionConfig` + rute + sidebar + master item + izin dept Ops | menu muncul untuk tim Ops |
| 3 | Form kunjungan: baris item + foto + tombol "Nihil temuan" | **sudah bisa dipakai mencatat** |
| 4 | Halaman tren mingguan + daftar kunjungan | mingguan terbaca |
| 5 | Rekap bulanan/tahunan + pembanding antar tahun | menggantikan Excel |
| 6 | Importer legacy 2025–2026 (42 baris, `rekap_legacy`) | riwayat masuk, pembanding tahun jalan |
| 7 | Laporan bulanan cetak A4 + tanda tangan otomatis | menggantikan Excel untuk distribusi |

Langkah 1–3 sudah cukup untuk mulai mencatat; 4–7 menambah nilai di atas data
yang sudah masuk. Langkah 6 bisa dikerjakan kapan saja setelah 1 — tidak
bergantung pada langkah lain.

### Fase 2 — menyusul, tanpa migrasi ulang

| # | Langkah | Prasyarat |
|---|---|---|
| 8 | Master area + rincian area di form + titik rawan di laporan | daftar titik dari tim Ops |
| 9 | Notifikasi ambang | ±3 bulan data mingguan terkumpul |

Keduanya sengaja ditunda karena **prasyaratnya data, bukan kode** — keduanya
akan salah bentuk kalau dikerjakan sebelum prasyaratnya ada.

---

## 9. Keputusan

**Tidak ada lagi yang menghambat.** Seluruh pertanyaan terbuka sudah terjawab
atau ditunda dengan alasan yang jelas.

### 9.1 Sudah diputuskan

- Satuan simpan **kunjungan bertanggal**, minggu dihitung saat tampil (§2).
- Penomoran minggu **ISO Senin–Minggu** (§2.1).
- Bentuk form **hibrida** — baris item + rincian area opsional (§4.1).
- Lokasi/area dan foto bukti **ikut dicatat**; catatan tindakan tidak.
- Pemilik modul & penginput: **tim Ops** — dept Operational & Building
  Maintenance, `can_view` + `can_edit` (§4.2). Menutup sekaligus pertanyaan
  jalur input-only dan panjang jendela input: dua-duanya tidak diperlukan.
- **Satu kunjungan per mall per hari** — `UNIQUE(mall, tanggal)` (§3.3).
- **Rincian area ditunda ke Fase 2**; tabel & kolomnya tetap dibuat sekarang
  agar tidak ada migrasi ulang (§3.2, §4.1).
- **Data 2025 & 2026 diimpor** sebagai `rekap_legacy` (§6).
- **Notifikasi ditunda** sampai ada ±3 bulan data mingguan (§7).

### 9.2 Ditinjau ulang nanti, bukan sekarang

| Hal | Ditinjau saat |
|---|---|
| Daftar area `pest_areas` | tim Ops menyerahkan daftar titik |
| Ambang notifikasi | ±3 bulan data mingguan terkumpul |
| Longgarkan `UNIQUE(mall, tanggal)` | bila ternyata ada sesi pagi/sore terpisah |

### 9.3 Dipastikan sebelum rilis produksi

Angka dan susunan jabatan di §4.2 dan §5 diperiksa di **basis data lokal**.
Sebelum seed izin dan laporan dijalankan di produksi, pastikan di sana: id
departemen Operational & Building Maintenance, dan siapa yang benar-benar
mengisi blok Disusun / Diperiksa / Mengetahui.
