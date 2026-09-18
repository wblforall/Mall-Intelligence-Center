# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Mall Intelligence Center — sistem manajemen event untuk dual-mall PT. Wulandari Bangun Laksana Tbk. Versi saat ini: **v2.26.0** (September 2026).

Stack: CodeIgniter 4 (v4.4.x), MySQL (XAMPP), Bootstrap 5.3, Chart.js, Apache.  
Base URL: `http://localhost/mall-intelligence-center/public/`

---

## Commands

```bash
# Jalankan semua test
composer test
# atau langsung
vendor/bin/phpunit

# Jalankan satu test suite
vendor/bin/phpunit tests/unit/

# Generate coverage report
vendor/bin/phpunit --colors --coverage-text=tests/coverage.txt --coverage-html=tests/coverage/ -d memory_limit=1024m

# Migrasi database
php spark migrate

# Rollback migrasi
php spark migrate:rollback

# Buat migration baru
php spark make:migration NamaTable
```

---

## Arsitektur & Flow Penting

### Event Lifecycle

Status event **tidak disimpan di kolom** — dihitung otomatis oleh `EventModel::calcStatus()` berdasarkan tanggal hari ini dan data completion:

```
today < start_date         → DRAFT
today >= start_date        → ACTIVE
today > end_date           → WAITING DATA (ada modul belum selesai)
semua required modules complete → COMPLETED
```

Required modules (dari `EventCompletionModel::REQUIRED_MODULES`): `content`, `loyalty`, `vm`, `creative`, `exhibitors`, `sponsors`.

### canEdit — Mekanisme Penguncian Form

Setiap view menerima flag `canEdit`. Form terkunci (readonly) jika salah satu kondisi ini terpenuhi:
- `canEditMenu(menuKey)` → false (akses dept/role tidak cukup)
- `completion != null` → modul sudah ditandai selesai

Admin bisa membuka kembali dengan menghapus record di `event_completions`.

### Sistem Akses (Role & Departemen)

Session setelah login berisi:
- `user_id`, `name`, `role` (`'admin'` | `'user'`), `dept_id`, `dept_menus`, `role_perms`

Prioritas: `role='admin'` → bypass semua. Selanjutnya `role_perms` → `dept_menus`.  
Setiap controller memanggil `canViewMenu(key)` / `canEditMenu(key)` — jika false → redirect ke `/events`.

### Traffic Mapping

Traffic disimpan di `daily_traffic` (per pintu, per tanggal, per mall) — **bukan per event**. Pemetaan ke event dilakukan saat query berdasarkan `start_date` dan `event_days`. Jika dua event di tanggal yang sama (mall berbeda), traffic terhitung di keduanya.

### Content → Rundown Sync

Item content bertipe `'program'` memicu `EventRundownModel::syncFromContentItem()` secara otomatis saat add/edit. Item bertipe `'biaya'` tidak masuk rundown.

### EventFinanceService

`app/Services/EventFinanceService.php` menangani semua kalkulasi agregat budget/revenue/traffic menggunakan bulk query (9 query total untuk monthly summary, berapapun jumlah event). Jangan pernah hitung ulang angka-angka ini di luar service ini — gunakan method yang sudah ada.

### Activity Log

Semua operasi create/update/delete harus memanggil `ActivityLog::write()`. Log disimpan ke tabel `activity_logs` dan bersifat append-only.

### Notifikasi in-app & Kotak Persetujuan

Notifikasi lintas modul memakai **satu tabel `notifications`**. Kirim lewat `App\Libraries\Notify::send($userIds, $actorId, $module, $type, $title, $body, $linkType, $linkId, $url)` — pengirim otomatis dikecualikan, `insertBatch` (1 query).

Penerima diresolusi lewat `App\Libraries\OrgRecipients`: `deptHead()` (grade terendah ≥5), `deputy()` (grade 3 per divisi), `gm()`, `menuEditors($menuKey)`, `withRolePerm($perm)`, `admins()`.

⚠️ **Bungkus dengan `OrgRecipients::orAdmins()`** untuk notifikasi yang tak boleh hilang — `department_menu_access` tidak memuat grant `hr_main`/`legal` (akses lewat role admin), sehingga `menuEditors()` bisa mengembalikan kosong dan notifikasi lenyap tanpa jejak.

⚠️ **Setiap AJAX POST wajib menyinkronkan token CSRF ke seluruh form di halaman** (`Config\Security::$regenerate = true` merotasi token tiap POST; form yang sudah ter-render jadi basi dan submit berikutnya ditolak diam-diam). Pola: kembalikan `csrf_hash()` di respons JSON, lalu tulis ulang semua `input[name=mic_csrf_token]`. Jangan pakai `navigator.sendBeacon` untuk POST ber-CSRF — responsnya tak bisa dibaca; pakai `fetch(..., {keepalive:true})`.

**Kotak Persetujuan** (`/persetujuan`): `App\Libraries\ApprovalInbox::collect($ctx)` mengagregasi 8 sumber pending. Otorisasi **tidak** ditebak di library — controller menyerahkan konteks kapabilitas hasil `canEditMenu()/can()`, agar aturan akses tetap satu sumber di `BaseController`. Batas `BATAS_PER_SUMBER` per sumber.

### Laporan Bulanan (print formal)

Lima modul punya laporan bulanan siap cetak (`?bulan=YYYY-MM`, tombol di halaman Summary): Loyalty (`loyalty/summary/print`), Sponsorship (`sponsorship/summary/print`), Traffic (`traffic/laporan-bulanan`), Parkir Pendapatan (`parking/revenue/laporan-bulanan`), Parkir Kendaraan (`parking/vehicles/laporan-bulanan`).

Saat menambah laporan modul baru, ikuti pola baku:
- View standalone A4 landscape **font 11px**; pakai partial bersama `app/Views/_laporan/_style.php` (CSS + aturan page-break: `tbody.prog-block` per item, thead berulang) dan `_laporan/_ttd.php` (blok tanda tangan).
- Tanda tangan via `App\Libraries\ReportSignatories::resolve(menuKey)` — dept penyusun = **dept pemilik modul** (pemegang `can_edit` di `department_menu_access`, non-outsource), bukan dept si pencetak. Disusun = Dept Head, Diperiksa = Senior Manager divisi (grade 4, bila ada) berdampingan Deputy GM (grade 3) dalam satu kolom, Mengetahui = GM.
- Struktur isi: KPI (delta vs bulan lalu) → rekap per mall → Ringkasan Analisa (insight rule-based) + 2 grafik Chart.js (`animation:false`, palet CVD-safe) → tabel detail (pembanding `lalu · kum` untuk periode multi-bulan) → ttd.

### Pest Control — v2.26

**Satuan simpan = satu kunjungan pada satu tanggal.** Nomor minggu TIDAK pernah
disimpan; dihitung saat tampil lewat `YEARWEEK(tanggal, 1)` (ISO, sama dengan
`WorkReportCtrl`). Inilah yang membuat perbedaan bulan 4 dan 5 minggu tidak pernah
perlu diputuskan di tingkat data. Rancangan lengkap: [PESTCARE_DESIGN.md](PESTCARE_DESIGN.md).

⚠️ **`UNIQUE(mall, tanggal, sumber)`, bukan `(mall, tanggal)`.** Baris
`sumber='rekap_legacy'` mewakili satu BULAN (hasil impor Excel), baris
`sumber='kunjungan'` mewakili satu HARI. Kunci tanpa `sumber` membuat keduanya
berebut tanggal dan kunjungan sungguhan tak bisa dicatat. `getByMallTanggal()`
menyaring `sumber='kunjungan'` agar form tak pernah memungut baris impor.

⚠️ **Nol tidak disimpan** — jumlah 0 menghapus barisnya. Yang membuktikan
pemeriksaan dilakukan adalah keberadaan baris `pest_visits`, bukan angka nol. Karena
itu tombol **"Nihil temuan"** wajib ada; tanpanya "diperiksa, bersih" tak bisa
dibedakan dari "belum diinput" — persis lubang Excel lama.

⚠️ **Dua definisi periode yang sengaja berbeda.** Tren mingguan (`/pest`) memakai
minggu ISO penuh dan mengecualikan `rekap_legacy`. Laporan bulanan memotong minggu
di batas bulan (ditandai "sebagian") dan memuat `rekap_legacy`. Jangan disamakan —
keduanya diberi keterangan di layar.

⚠️ **Urutan tulis berkas foto kebalikan dari urutan hapus, dan keduanya benar:**
unggah menyimpan ke disk SEBELUM menulis DB (gagal di tengah → `unlink`, nol baris
DB); hapus menghapus berkas SESUDAH `transComplete()`. Yang tidak bisa dibatalkan
dikerjakan belakangan.

⚠️ **`public/uploads/pest` harus 777**, bukan 755 — Apache berjalan sebagai user lain
sehingga `mkdir` subfolder per-kunjungan gagal dan unggah menolak dengan 500.

Importer legacy: `php spark mic:pest-import-legacy [--dry]`, sumber
`data/pest-legacy-2025-2026.csv`. Aman dijalankan ulang — pemeriksaan "sudah
terimpor" dilakukan **per bulan, bukan per tanggal**, karena tanggal bulan berjalan
dibatasi ke hari ini dan akan berbeda bila dijalankan di hari lain.

⚠️ **Tombol filter aktif: jangan pakai `.active`.** `theme.css` menimpa
`.btn-outline-*` dan `.btn-secondary` dengan `!important`, sehingga label tombol aktif
jadi tak terbaca. Pola MIC: tukar kelasnya — aktif `btn-primary`, nonaktif
`btn-outline-secondary`.

### Pengkinian Data Mandiri (ESS) — v2.25

**Satu pintu.** Data pribadi DAN berkas buktinya diajukan bersamaan lewat modal *Ajukan Perubahan Data* (`Users::submitChange`). Form *Unggah Dokumen Lain* hanya melayani jenis `lainnya`. Jangan menambahkan jalur unggah kedua untuk jenis dokumen yang sudah punya pasangan data — dua pintu untuk berkas yang sama terbukti membingungkan dan melahirkan berkas ganda.

**Bukti wajib.** Mengubah nomor identitas menuntut lampiran kartunya (`periksaBuktiIdentitas`); mengubah data pendidikan menuntut ijazah, plus transkrip untuk D1 ke atas (`periksaBuktiPendidikan`). Keduanya diperiksa **sebelum** satu pun baris dibuat — menolak di tengah jalan menyisakan sebagian pengajuan tersimpan dan sebagian tidak. Tidak dituntut ulang bila berkasnya sudah `approved`/`pending`.

**Lampiran boleh berdiri sendiri.** Kiriman berisi berkas saja (tanpa perubahan field) tetap sah — tanpa ini karyawan yang nomornya sudah benar tapi berkasnya ditolak akan buntu di pesan "tidak ada perubahan".

**Satu dokumen per jenis** kecuali `lainnya` (`EmployeeDocumentModel::sekaliSaja()`, diturunkan dari `JENIS` agar jenis baru otomatis ikut terlindungi). Ditegakkan di form DAN server.

⚠️ **OCR (`public/js/ocr-identitas.js`) berjalan penuh di browser** — foto identitas tidak boleh dikirim ke layanan luar. Aset di `public/lib/tesseract` + `public/lib/pdfjs` (dimuat malas), ditaruh di `lib/` karena `.gitignore` mengecualikan `vendor/`. Dua pelajaran mahal: **jangan praproses gambar** (grayscale/threshold justru merusak — Tesseract binarisasi sendiri), dan pencarian nomor **berbasis label**, bukan panjang (di KK, NIK dan nomor KK sama-sama 16 digit). Hasil tebakan hanya dipakai bila labelnya terlihat dan kandidatnya tunggal — kolom kosong lebih baik daripada angka keliru yang tampak sahih.

⚠️ **Setiap mengubah `ocr-identitas.js` atau `theme.css`, naikkan `?v=` di view/layout pemanggilnya.** Tanpa itu browser karyawan memakai berkas lama dan perbaikan tak pernah aktif.

---

## Struktur Database

Tabel pusat: `events`. Semua modul event berelasi via `event_id`.

Tabel independen (tidak terikat `event_id`):
- `daily_traffic`, `daily_vehicles` — traffic harian per pintu/tanggal
- `loyalty_programs` + child tables — loyalty standalone (bukan per event)

Lihat diagram lengkap di [ARCHITECTURE.md](ARCHITECTURE.md#3-modul--tabel-database).

---

## Konvensi Kode

- **Satu controller per modul**, satu model per tabel utama.
- Multi-table delete: gunakan **database transaction** (`$db->transStart()` / `$db->transComplete()`). Hapus file fisik hanya setelah commit berhasil.
- Kalkulasi budget/revenue/traffic: selalu via `EventFinanceService`, bukan query langsung di controller.
- Grouping/filtering data: lakukan di controller, bukan di view.

---

## Integrasi Eksternal (Direncanakan)

| Sistem | Tipe | Entry Point |
|---|---|---|
| CLARA (ERP) | MySQL cross-database query | Modul Budget |
| Purchasing | REST API | Modul VM & Creative |
| PAM Plus (membership) | REST API | Modul Loyalty |

Belum ada integrasi aktif di v2.0.
