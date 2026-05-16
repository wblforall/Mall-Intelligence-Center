# Release Note — Mall Intelligence Center

> Versi saat ini: **v1.5** (Mei 2026)

**Dikembangkan oleh:**
IT Department — PT. Wulandari Bangun Laksana Tbk.

| Peran | Nama |
|-------|------|
| Head Developer | Ahmad Affan Ridha |
| Developer | Mochamad Sa'adillah Effendi |
| Implementor | Riky Akbar |

---

## Versi 1.5

**Tanggal Rilis:** 16 Mei 2026

### Perubahan dari v1.4

#### Fitur Baru

- **Theme Periods** — sistem tema visual berbasis periode kalender (CRUD admin). Mendukung 5 tipe animasi layar: confetti, balon, salju, kembang api, dan bintang. Animasi muncul otomatis saat tanggal hari ini berada dalam periode aktif.
- **Greeting Post-Login & Weather Widget** — popup sapaan muncul setelah login dengan data cuaca real-time dari Open-Meteo (suhu, kondisi, angin). Widget cuaca juga ditampilkan permanen di header dashboard. Reminder period aktif ditampilkan di widget terpisah.
- **Dashboard — Indikator Ekonomi** — blok indikator makro baru di dashboard:
  - BBM (Pertalite, Pertamax, Pertamax Turbo) — auto-fetch harian dari MyPertamina API dengan cache lock
  - GDP Nasional & PDRB Balikpapan — input manual via admin modal, tersimpan ke DB
  - BI Rate & Inflasi — async fetch dari BI.go.id dengan fallback graceful
  - Kurs USD — date-based fetch via jsDelivr, fallback ke @latest
  - Segmen Pengunjung Prospektif — 6 segmen dinamis berdasarkan kombinasi indikator makro
  - Badge LIVE/MANUAL dan tanggal update per indikator
- **Traffic — Merge Pintu Bernomor (Funstation & XXI)** — pintu bernomor (mis. Funstation 1–3, XXI 1–2) digabung menjadi satu baris di halaman Summary Traffic dan Compare Traffic, memperjelas pembacaan data per lokasi pintu.

#### Perbaikan Bug

- **Fix: Total deal sponsor barang** — perhitungan total deal untuk sponsor jenis Barang kini mengalikan `qty × nilai` per item (sebelumnya hanya menjumlahkan nilai tanpa qty). Cash dan Barang juga dipisahkan dalam kalkulasi total di kedua modul (event & standalone).
- **Fix: Sel kosong EEI matrix di dark mode** — background `#f8f9fa` diganti `var(--bs-secondary-bg)` agar konsisten di semua tema.
- **Fix: Table row warna di dark mode** — `table-warning`, `table-info`, `table-danger` pada halaman Events diubah ke `rgba` agar tidak memblokir teks di dark mode.
- **Fix: Traffic card overflow di dark mode** — tambah `overflow:hidden` pada card traffic untuk memperbaiki sudut kotak yang patah.
- **Fix: DB charset utf8 → utf8mb4** — charset database diperbarui untuk mendukung emoji dan karakter Unicode 4-byte.

#### Animasi & UX

- **Global fadeUp animation** — animasi entrance `fadeUp` ditambahkan ke `layouts/main.php` sehingga berlaku di seluruh halaman aplikasi.
- **Stagger entrance** — animasi stagger tambahan pada halaman: Traffic, Traffic Doors, Event Locations, Users, Departments, Roles, Logs, Admin (Clusters, Divisions, Jabatans), dan EEI.
- **EEI dim-bar** — bar indikator EEI kini animate dari 0 ke nilai aktual saat halaman pertama kali render.

#### Dev & Ops

- **Production deployment ke Rumahweb** — aplikasi kini dapat diakses publik di `mic.wbl-bsb.com` (LiteSpeed shared hosting).
- **Autobackup via Cron Job** — backup otomatis database dan uploads 2× sehari (08.00 & 18.00 WITA), disimpan ke `/home/wblc8418/backups/`, auto-clean setelah 30 hari.
- **`.htaccess` per-environment** — file `.htaccess` (root & public) dikeluarkan dari git tracking; konfigurasi lokal (Apache/XAMPP) dan production (LiteSpeed) dikelola terpisah.

---

## Versi 1.4

**Tanggal Rilis:** 12 Mei 2026

### Perubahan dari v1.3

#### Fitur Baru

- **Traffic Summary — Weekday vs Weekend Breakdown** — kartu breakdown tambahan di halaman Summary Traffic yang memisahkan total & rata-rata pengunjung harian antara Weekdays (Senin–Kamis) dan Weekend (Jumat–Minggu), lengkap dengan breakdown per mall (eWalk / Pentacity) dan jumlah hari aktif masing-masing segmen.
- **Traffic Summary — Event dalam Periode** — badge pills di bagian bawah halaman Summary Traffic menampilkan semua event yang berlangsung pada periode yang dipilih, mencakup nama event dan rentang tanggalnya.
- **Traffic Compare — KPI Kendaraan di Baris Terpisah** — KPI card kendaraan (Mobil, Motor, Total Kendaraan) dipindah ke baris kedua terpisah dari KPI pengunjung, memperjelas pemisahan kategori metrik.
- **Traffic Compare — Weekday vs Weekend per Periode** — tabel perbandingan Weekday vs Weekend per periode yang di-compare, menampilkan total dan rata-rata harian untuk masing-masing segmen dan masing-masing periode.
- **Traffic Compare — Event per Periode** — setiap periode yang di-compare menampilkan badge event yang berlangsung dalam rentang tanggal tersebut.
- **Print Summary Traffic — Weekday/Weekend & Events** — halaman cetak standalone summary traffic kini menyertakan kotak Weekday vs Weekend (sebelum tabel harian) dan daftar event dalam periode (setelah tabel per pintu).
- **Print Compare Traffic** — halaman cetak standalone baru (`/traffic/print-compare`) untuk modul Compare Traffic. Menampilkan: KPI pengunjung & kendaraan antar periode, tabel Weekday vs Weekend, event per periode, chart harian (bar) dan per jam (line), serta tabel per pintu eWalk dan Pentacity — semua dalam layout landscape A4, dengan auto-print saat halaman dimuat.

#### Perbaikan Bug

- **Fix: Function redeclaration fatal error** — fungsi PHP `pctDiff()`, `diffBadge()`, `kpiCard()` di `compare.php` dan `pctDiffPrint()`, `diffCell()` di `print_compare.php` kini dibungkus guard `if (! function_exists(...))` untuk mencegah error "Cannot redeclare" jika view di-render lebih dari sekali dalam satu request.
- **Fix: `$hasVehicleData` used before definition** — definisi `$hasVehicleData` dipindah ke blok awal PHP di `compare.php` agar tersedia saat render baris KPI kendaraan.

---

## Versi 1.3

**Tanggal Rilis:** 10 Mei 2026

### Perubahan dari v1.2

#### Fitur Baru

- **Modul Sponsorship Standalone** — modul baru untuk mengelola program sponsorship di luar event, mengikuti pola kerja Loyalty Standalone.
  - **Program** — buat program sponsorship dengan target sponsor, target nilai, tanggal, status aktif/nonaktif, dan lock/unlock (admin only).
  - **Deal Sponsor** — kelola sponsor per program: nama, kategori (Platinum/Gold/Silver/Bronze/Media Partner/In-kind/Lainnya), jenis (Cash / Barang), status deal (Prospek → Negosiasi → Terkonfirmasi → Lunas / Batal), detail, catatan.
  - **Rincian Barang** — untuk jenis Barang/In-kind, input item per baris (deskripsi, qty, nilai); nilai deal dihitung otomatis dari total item.
  - **Realisasi** — catat penerimaan aktual per sponsor (tanggal, nilai, catatan, upload bukti). Mendukung realisasi parsial (multiple entries).
  - **Budget Auto-Sync** — budget program dihitung otomatis dari total nilai sponsor berstatus Terkonfirmasi dan Lunas.
  - **KPI Dashboard** — 4 KPI card: Program Aktif, Sponsor Konfirmasi, Nilai Deal, Total Terkumpul beserta progress vs target.
  - **Collection Rate** — progress bar persentase realisasi vs nilai deal konfirmasi per program.
  - **Halaman Summary Bulanan** — trend realisasi bulanan (tahun berjalan), chart realisasi harian (Chart.js bar), tabel breakdown per program (target nilai, dikonfirmasi, bulan ini, all-time, progress bar), dan breakdown status deal per program.

#### UX & Tampilan

- **Thousand separator otomatis** — semua input nilai Rp di modul Sponsorship (target nilai, nilai deal, nilai item barang, nilai realisasi) menampilkan separator ribuan titik secara real-time saat mengetik. Nilai juga diformat saat modal edit dibuka.
- **Indikator realisasi parsial** — kolom Terkumpul pada tabel sponsor menampilkan persentase pencapaian (mis. `67%`) untuk realisasi parsial, dan badge ✓ hijau saat realisasi sudah mencapai atau melewati nilai deal.
- **Edit sponsor barang** — modal Edit Sponsor kini mendukung rincian barang: item yang sudah tersimpan ditampilkan dan bisa diubah, ditambah, atau dihapus. Toggle otomatis antara field Nilai (cash) dan tabel items (barang).
- **Urutan kategori sponsor** diperbaiki: Platinum → Gold → Silver → Bronze → Media Partner → In-kind → Lainnya.

#### Perbaikan Bug

- **Fix: badge Cash/Barang dan badge item barang tidak terbaca di dark mode** — `bg-light text-dark` diganti dengan warna eksplisit (`#e2e8f0 / #334155`) agar konsisten di semua tema.
- **Fix: double-highlight nav parent + child** — link toggle sidebar (Sponsorship, Loyalty, Creative & Design, Dekorasi & VM) tidak lagi mendapat class `active` bersamaan dengan item submenu yang aktif.

---

## Versi 1.2

**Tanggal Rilis:** Mei 2026

### Perubahan dari v1.1

#### Fitur Baru

- **VM Deadline** — field `Tanggal Deadline` ditambahkan pada item Dekorasi & VM, tersedia di halaman event VM maupun standalone. Jika sudah melewati deadline, ditampilkan badge merah **Lewat Deadline** di samping tanggal. Kolom Deadline juga ditambahkan di tabel Summary Bulanan VM.
- **Sort Event berdasarkan Start Date** — daftar event kini diurutkan berdasarkan `start_date ASC` (event terlama di atas) menggantikan `created_at DESC`.

#### Animasi & UX

- **Animasi halaman Events** — entrance animation `fadeUp` pada baris tabel dengan stagger per baris (capped 8 baris), serta slide compare bar.
- **Animasi halaman Summary Event** — KPI cards fade-up bertahap, count-up angka Rp untuk nilai budget, realisasi, revenue, dan profit.
- **Animasi halaman Content Event** — header, description card, KPI row, section header, dan item card stagger via JavaScript (program & biaya cards masing-masing stagger terpisah).

#### Activity Log

- `ActivityLog::write()` ditambahkan ke controller yang sebelumnya belum mencatat operasi write:
  - **Departments** — create, update, delete
  - **LoyaltyCtrl** — 16 operasi: hadiah item, hadiah realisasi, voucher item, voucher realisasi, program (create/update/delete), toggleStatus, lock, unlock, realisasi member
  - **EventTracking** — create, update, delete
  - **EventTenants** — store, update, delete, saveImpact
  - **EventBaseline** — save
  - **EventInputs** — save (create & update path)

#### Dev & Ops

- **Git versioning** — repository diinisialisasi dengan `git init`, `.gitignore` dikonfigurasi (exclude `vendor/`, `writable/`, `public/uploads/`, `.env`, file referensi besar).
- **DEPLOY.html** — panduan deploy lengkap untuk transfer via USB ke server lokal: export DB, copy kode, git pull, migrate, serta prosedur update rutin.
- **Versi & copyright** ditampilkan di sidebar footer aplikasi.

---

## Versi 1.1

**Tanggal Rilis:** Mei 2026

### Perubahan dari v1.0

#### Fitur Baru

- **Exhibition Target** — dapat menetapkan target jumlah exhibitor dan target nilai dealing per event. Tombol "Set Target" tersedia di halaman Exhibition; progress bar pencapaian ditampilkan di summary strip. Target juga muncul di Summary Event dan Print Post Event.
- **Achievement Badge — Summary Bulanan Loyalty** — strip agregat di atas per-program section menampilkan berapa program yang fully achieved, partial, atau belum mencapai target bulan tersebut. Setiap card program juga menampilkan badge pencapaian individual.
- **Redesign Summary Bulanan Loyalty** — tampilan per-program diubah dari tabel kompak menjadi card grid yang detail. Section dibagi dua: **Program Loyalty Standalone** dan **Support Event**. Setiap card menampilkan member stats (baru + aktif), voucher (sebar + terpakai), hadiah, progress bar vs target, dan link ke halaman detail.
- **Target Penyerapan Voucher di Summary Bulanan** — kolom `target_penyerapan` per item voucher kini ditampilkan di tabel List Voucher pada halaman Summary Bulanan Loyalty.
- **Nama Program & Event di List Voucher/Hadiah** — list voucher dan hadiah di Summary Bulanan Loyalty kini mencantumkan nama program serta nama event asal (untuk program yang berasal dari event), ditampilkan dengan warna pembeda.
- **Lokasi Event di Summary Event** — lokasi event (titik-titik dalam mall) kini ditampilkan di baris metadata header halaman Summary Event.
- **e-Voucher tampil di Program Loyalty** — program dengan `target_type = evoucher` yang menyimpan config voucher di level program (bukan per item) kini menampilkan informasi nilai, jumlah diterbitkan, dan target penyerapan di halaman Program Loyalty.

#### Perbaikan Bug

- **Fix: `$totalDealing` undefined di Print Post Event** — blok kalkulasi exhibition target dipindah ke setelah variabel `$totalDealing` didefinisikan, menghilangkan error saat mencetak laporan post event.
- **Fix: `Undefined array key 'deskripsi'` di Print Technical Meeting** — tiga titik akses array tanpa null-coalescing di view technical meeting (`$r['deskripsi']`, `$sp['deskripsi']`, `$ci['deskripsi']`) sudah diperbaiki dengan `?? null` / `?? ''`.

#### Perbaikan & Peningkatan Kualitas Kode

- **Database Transaction pada operasi hapus multi-tabel** — semua operasi delete yang menyentuh lebih dari satu tabel kini dibungkus transaction, mencegah data inconsistency jika terjadi kegagalan di tengah proses. Berlaku di modul: Loyalty, VM, Creative, Sponsorship, Content.
- **File fisik hanya dihapus setelah DB berhasil** — urutan unlink file dipindah ke setelah `transComplete()` pada semua modul yang memiliki upload file.
- **EventFinanceService** — service baru untuk agregasi budget, revenue, traffic, dan kendaraan secara bulk query, menghilangkan N+1 query di halaman Summary Bulanan.
- **Duplikasi kalkulasi budget dieliminasi** — seluruh perhitungan total budget event kini terpusat di `EventFinanceService::getBudgetTotal()`.
- **Logic grouping & filtering dipindah dari view ke controller** — data transformasi (grouping array, filter, agregasi) tidak lagi dilakukan di layer view. Berlaku pada 8 controller/view: Loyalty, VM, Sponsors, Content, Creative, EventSummary (index, technicalMeeting, postEvent).
- **Fix: $trafficModel undefined di Summary Bulanan** — model traffic & vehicle tidak diinstansiasi di method monthly(), sudah diperbaiki.

#### Halaman Budget Event — Redesign & Perbaikan

- **Redesign halaman Budget Event** — tampilan diubah total: 4 KPI card (Total Budget dengan breakdown per kategori, Revenue, Profit/Loss, ROI dengan progress bar), stacked allocation bar berwarna per kategori, dan tabel detail per modul (Departemen, Loyalty, VM, Content, Creative) dalam satu halaman penuh.
- **Fix: Creative budget tidak terhitung di total** — `$creativeBudget` dari `EventCreativeItemModel::getTotalBudget()` kini disertakan dalam kalkulasi `$totalBudget`, menyamakan angka dengan `EventFinanceService::getBudgetTotal()`.
- **Revenue & Profit/ROI ditampilkan di Budget page** — controller kini mengambil `EventFinanceService::getRevenueTotal()` dan mengirimkannya ke view, sehingga konteks finansial (profit dan ROI) tersedia langsung di halaman budget.
- **Detail Budget Content Event & Creative** — item-item dari `event_content_items` dan `event_creative_items` kini ditampilkan sebagai tabel detail di halaman Budget, melengkapi tabel Loyalty dan VM yang sudah ada sebelumnya.
- **Hapus form input budget departemen** — form "Budget Departemenku", method `saveBudget()`, dan route POST budget dihapus; halaman Budget kini murni read-only summary. Variabel unused (`$myBudgets`, `$canEdit`, `$deptId`) dibersihkan dari controller.

---

## Versi 1.0

**Tanggal Rilis:** Mei 2026

### Deskripsi
Rilis perdana Mall Intelligence Center — sistem manajemen dan monitoring event terpadu untuk eWalk Simply FUNtastic dan Pentacity Shopping Venue. Sistem ini mencakup seluruh siklus event dari perencanaan, pelaksanaan, hingga laporan post-event, dengan akses berbasis departemen.

---

### Fitur yang Tersedia

#### 🔐 Autentikasi & Manajemen User
- Login & logout dengan session management
- Manajemen user (admin)
- Role-based access control (Admin, Dept User)
- Akses menu berbasis departemen — tiap dept hanya melihat modul yang relevan
- Activity log — seluruh aktivitas user tercatat otomatis

#### 📅 Manajemen Event
- Buat, edit, dan hapus event
- Status event otomatis: Draft → Active → Waiting Data → Completed
- Informasi event: nama, tema, mall, tanggal mulai, durasi hari
- Perbandingan antar event
- Gallery foto per event
- Lokasi event (multi-titik dalam mall)

#### 📊 Summary Event
- Halaman summary per event: KPI budget, revenue, profit margin
- Chart traffic pengunjung harian & kendaraan harian
- Detail exhibition, sponsorship, loyalty, VM, content, creative dalam satu halaman
- Status penyelesaian data per modul
- Print Technical Meeting
- Print Laporan Post Event
- **Summary Bulanan** — agregat semua event dalam satu bulan dengan chart budget vs revenue

#### 💰 Budget Event
- Input rencana budget per departemen per event
- Breakdown budget: departemen, loyalty, VM, content, creative
- Tracking realisasi budget vs rencana

#### 🚶 Traffic Pengunjung & Kendaraan
- Input traffic harian per pintu masuk per jam (eWalk & Pentacity)
- Input kendaraan harian (mobil & motor)
- Master pintu — konfigurasi pintu masuk per mall
- Summary traffic dengan filter periode
- Perbandingan traffic antar periode
- Import data traffic (bulk)

#### ⭐ Program Loyalty
- **Standalone** — program loyalty di luar event
- **Per event** — program loyalty terikat event tertentu
- Tipe program: member, voucher, hadiah
- Manajemen voucher: penerbitan, sebaran, pemakaian
- Manajemen hadiah: stok, distribusi
- Realisasi member per program
- Lock/unlock program
- Summary bulanan program loyalty

#### 🏪 Exhibition (Casual Leasing)
- Manajemen exhibitor per event (kategori, lokasi booth, nilai dealing)
- Program per exhibitor (jadwal, jam, lokasi)
- Total dealing otomatis per event

#### 🏆 Sponsorship
- Manajemen sponsor per event (cash & in-kind)
- Detail item sponsor in-kind (deskripsi, qty)
- Realisasi sponsor
- Total cash & in-kind otomatis

#### 🎨 Visual Merchandising (VM)
- Manajemen item VM per event (nama, deskripsi referensi, budget)
- Input realisasi per item (nilai, foto bukti pemasangan)
- Tracking budget vs realisasi VM

#### 📹 Content Event
- Manajemen item content per event (tipe: program/biaya, jenis, jadwal, lokasi, PIC)
- Input realisasi per item
- Rundown event — penjadwalan program per hari
- Print rundown

#### ✏️ Creative & Design
- Manajemen item creative per event:
  - Master Design (dengan status: draft, review, approved, revision)
  - Digital (jadwal take, PIC)
  - Cetak
  - Influencer
  - Media Prescon
- Upload file per item creative
- Input realisasi per item
- Input insight digital (views, reach, impressions, likes, shares, followers)
- Tracking serah terima & bukti terpasang

#### ⚙️ Sistem & Admin
- Manajemen departemen
- Manajemen role & hak akses menu per departemen
- Master traffic doors per mall
- Master lokasi event

---

### Teknologi
- **Framework:** CodeIgniter 4
- **Database:** MySQL
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Server:** Apache (XAMPP)

---

*© 2026 IT Department — PT. Wulandari Bangun Laksana Tbk. All rights reserved.*
