# Release Note — Mall Intelligence Center

> Versi saat ini: **v1.2** (Mei 2026)

**Dikembangkan oleh:**
IT Department — PT. Wulandari Bangun Laksana Tbk.

| Peran | Nama |
|-------|------|
| Head Developer | Ahmad Affan Ridha |
| Developer | Mochamad Sa'adillah Effendi |
| Implementor | Riky Akbar |

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
