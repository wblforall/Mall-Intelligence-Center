# Rencana Pengembangan Aplikasi Mobile MIC

**Mall Intelligence Center — PT. Wulandari Bangun Laksana Tbk.**
Disusun 2 Agustus 2026 · Basis: MIC v2.24.0 · Status: **rencana, belum ada kode app**

---

## 1. Prinsip Dasar (jangan dilanggar)

| Prinsip | Konsekuensi |
|---|---|
| **App = shell tipis di atas API MIC**, bukan sistem baru | Tidak ada tabel baru khusus app, tidak ada porting logika bisnis. Satu backend CI4 + MySQL yang sudah ada. |
| **Otorisasi memakai ULANG aturan MIC** | App **tidak boleh** jadi pintu belakang. Setiap endpoint wajib lewat aturan yang sama dengan `BaseController` (`canViewMenu` / `canEditMenu` / `canApproveMenu`). |
| **Native hanya menang di 3 hal** | (1) push notification andal (Web Push tidak andal di iOS), (2) antrian offline untuk input lapangan, (3) kamera cepat multi-foto. **Selain 3 ini, PWA yang sudah ada sudah cukup** — jangan pindahkan fitur ke app tanpa alasan dari daftar ini. |
| **Input berat tetap di web** | Layar kecil + jari bukan tempat yang tepat untuk grid, import Excel, scoring, dan master data. |

**Stack:** Flutter (konsisten dengan rencana Security Patrol) · Bearer token (`api_tokens`, sudah ada) · FCM untuk push · antrian lokal SQLite + idempotency key untuk offline.

---

## 2. Kondisi Backend Saat Ini (per v2.24.0)

### Yang SUDAH ada
- `app/Controllers/Api/` — `BaseApiController` dengan Bearer token, plus 6 controller: Auth, Dashboard, Events, PromoMedia, Idp, Pip.
- Endpoint hidup: `auth/login|me|logout|push-token`, `dashboard/summary`, `events` (index/show), approval **media-promo / idp / pip** (approve+reject).
- Tabel `api_tokens` sudah punya kolom **`push_token`** — tinggal mesin pengirimnya.
- **Tabel `notifications` + `App\Libraries\Notify::send()`** (v2.23) — pusat notifikasi in-app sudah berdiri.
- **`ApprovalInbox::collect($ctx)` + `contextForUser($userId)`** (v2.23) — agregator 9 sumber persetujuan yang **sengaja dibuat tanpa session**, memang disiapkan untuk API mobile. Ini menghemat pekerjaan paling besar di Fase 1.
- `ImageCompressor` (resize 1600px) untuk foto bukti.
- PWA installable (`sw.js`, manifest, offline.html) + tabel responsif `table-cardify`.

### CELAH yang WAJIB ditutup sebelum app dibangun

| # | Celah | Akibat kalau dibiarkan |
|---|---|---|
| 1 | `BaseApiController` **hanya membaca `department_menu_access`** — grant per-user (`user_menu_access`) tidak dimuat | User dengan akses khusus akan **ditolak keliru** oleh app padahal di web bisa. Sejak v2.24 makin parah: **hak "Setujui" per-orang sama sekali tak terbaca di API.** |
| 2 | `BaseApiController` belum punya `canApproveMenu()` | App tak bisa menentukan siapa boleh menyetujui — akan salah menampilkan/menyembunyikan tombol. |
| 3 | `/api/auth/me` hanya mengembalikan identitas (id, nama, email, role, dept) — **tanpa `role_perms` / peta menu** | App tak bisa menyusun menu & tombol sesuai hak akses, dan tak bisa menyegarkan hak setelah admin mengubahnya. |
| 4 | Tidak ada force-logout di API (token berlaku 30 hari) | Web memutus sesi saat `perms_changed_at` berubah atau `is_active=0`; app **tidak**. Karyawan resign masih bisa memakai app sampai token kedaluwarsa. |
| 5 | Belum ada mesin pengirim push | Kolom `push_token` terisi tapi tak pernah dipakai. Push = alasan utama bikin native; tanpa ini app kehilangan nilai intinya. |

> **Celah 1, 2, dan 4 adalah isu keamanan, bukan sekadar fitur.** Harus beres sebelum app dipakai orang.

---

## 3. Peta Fitur per Modul — Masuk App vs Tetap Web

**Legenda kolom "Di app":**
🟢 **Penuh** · 🔵 **Lihat + aksi terbatas** · ⚪ **Lihat saja** · 🔴 **Tidak masuk app**

### 3.1 Lintas Modul (fondasi app)

| Modul | Di app | Yang ADA di app | Yang TIDAK ada / tetap web |
|---|---|---|---|
| **Kotak Persetujuan** | 🟢 Penuh | Inbox terpadu 9 sumber, detail item, **Setujui / Tolak + alasan**, filter per modul, urut terlama, penanda "perlu segera" | Batch approve massal (lebih aman di web) |
| **Notifikasi** | 🟢 Penuh | **Push** + daftar notifikasi, tandai terbaca, deep-link ke itemnya | Pengaturan preferensi notifikasi (fase lanjut) |
| **Profil / ESS** | 🔵 | Lihat data pribadi, riwayat penilaian yang dirilis, **ajukan perubahan data**, ubah password | Upload dokumen berkas besar, buat akun karyawan |
| **Admin (User, Dept, Role, Jabatan, Settings, Hari Libur, Tema)** | 🔴 | — | **Seluruhnya tetap web.** Master data & pengaturan akses tidak pantas dipegang dari HP. |

### 3.2 Dashboard & Intelijen

| Modul | Di app | Yang ADA di app | Yang TIDAK ada / tetap web |
|---|---|---|---|
| **Dashboard Utama** | ⚪ Lihat | KPI ringkas, "Hari Ini di Mall", cuaca, hari libur terdekat | — |
| **Market Intelligence** | ⚪ Lihat | Strip angka kunci ekonomi (BI Rate, inflasi, kurs, IHSG, BBM), tab Kondisi/Insight/Daya Beli/Segmen | Grafik detail & tabel panjang → buka web |

### 3.3 Event

| Modul | Di app | Yang ADA di app | Yang TIDAK ada / tetap web |
|---|---|---|---|
| **Daftar Event** | 🔵 | Daftar + filter mall/status, detail event, **approve/tolak pengajuan event** | Buat & edit event |
| **Event Summary** | ⚪ Lihat | Ringkasan 6 modul wajib + status kelengkapan | — |
| **Content & Rundown** | ⚪ Lihat | Baca rundown & daftar konten | Tambah/edit item, sinkron rundown |
| **Loyalty per Event** | 🔵 | Lihat program, **foto bukti realisasi** (kamera + offline) | Setup program, input angka detail |
| **Creative per Event** | 🔵 | Lihat materi + preview, **approve / minta revisi**, **foto bukti** | Upload materi, input insight, edit item |
| **VM per Event** | 🔵 | Lihat item, **foto bukti pemasangan** | Input biaya & spesifikasi |
| **Exhibition** | ⚪ Lihat | Daftar exhibitor & status | Input & kontrak |
| **Sponsorship per Event** | 🔵 | Lihat sponsor & nilai deal, **foto bukti** | Input deal, realisasi angka |
| **Budget per Event** | ⚪ Lihat | Angka budget vs realisasi | Input & revisi anggaran |

### 3.4 Modul Standalone

| Modul | Di app | Yang ADA di app | Yang TIDAK ada / tetap web |
|---|---|---|---|
| **Progress Report** | 🟢 Penuh | Daftar program kerja, **isi update progress + foto (maks 5)**, baca & balas komentar, flag ke GM, dashboard rekap | Setup program kerja awal, arsip/pulihkan, cetak |
| **Traffic Harian** | 🟢 Penuh | **Input traffic per pintu per mall — offline + antrian**, revisi 3 hari terakhir, summary & perbandingan | Grid bulanan, import Excel, laporan bulanan cetak |
| **Parkir — Live** | ⚪ Lihat | **Slot parkir real-time** (okupansi & kapasitas per area) — dikonfirmasi masuk | — (memang read-only) |
| **Parkir — Kendaraan** | ⚪ Lihat | Grafik & angka kendaraan masuk/keluar | Laporan bulanan cetak |
| **Parkir — Revenue** | 🔴 | — | **Dikecualikan seluruhnya** (keputusan 2 Agu 2026) — data pendapatan tidak dibawa ke perangkat mobile. Endpoint API-nya pun tidak dibuat. |
| **Loyalty Standalone** | ⚪ Lihat | Daftar program & status, ringkasan bulanan | CRUD program, master tenant, input realisasi |
| **Sponsorship Standalone** | ⚪ Lihat | Daftar program & deal, ringkasan | CRUD program, input deal & realisasi |
| **Creative Standalone / Media Promo** | 🔵 | Daftar request + preview materi, **approve / tolak request Media Promo**, Gantt ringkas | Buat request, upload materi, atur spot & slot |
| **VM Standalone** | ⚪ Lihat | Daftar item & status | Input |
| **Stock Barang** | ⚪ Lihat | **Lihat stok & mutasi barang** (dikonfirmasi: hanya lihat) | Tambah stok, realisasi, CRUD barang — **seluruh input tetap web** |
| **Legal** | 🔵 | Daftar 7 entitas + status masa berlaku, peringatan jatuh tempo, **approve review dokumen** | Upload dokumen berversi, CRUD kontrak/perizinan |

### 3.5 HR & People Development

| Modul | Di app | Yang ADA di app | Yang TIDAK ada / tetap web |
|---|---|---|---|
| **Data Karyawan** | ⚪ Lihat | Cari & lihat profil karyawan, struktur organisasi | CRUD karyawan, import |
| **Pengajuan Data & Dokumen (HR)** | 🔵 | **Approve / tolak** pengajuan perubahan data & dokumen karyawan | — |
| **Appraisal** | 🟢 Penuh | Lihat form & hasil, **approve template & form**, dan **pengisian nilai (scoring) langsung dari app** — KPI (skor 0–100 + realisasi) dan Kompetensi (nilai 1–5), catatan penilai, teruskan ke penilai berikutnya, isi pendapat karyawan | Buat & edit template, import Excel, setup periode, cetak form |
| **PIP** | 🔵 | Lihat rencana, **approve / tolak**, baca review | Setup rencana & aspek |
| **IDP** | 🔵 | Lihat rencana, **approve / tolak** | Setup rencana |
| **TNA 360°** | 🔵 | **Isi kuesioner TNA** | Setup periode, master kompetensi, analisa hasil |
| **EEI** | 🔵 | **Isi survei EEI** | Setup survei, olah hasil |
| **Competencies** | ⚪ Lihat | Lihat kompetensi diri sendiri | Master & penilaian |
| **Training** | ⚪ Lihat | Riwayat training pribadi | Input & master |
| **Talent Portfolio 9-Box** | ⚪ Lihat | Lihat posisi matriks (bagi yang berhak) | Input penilaian berjenjang |

### 3.6 Ringkasan Cepat

**Yang benar-benar bisa DIKERJAKAN dari app (bukan sekadar dilihat):**
1. Menyetujui / menolak — 9 sumber persetujuan
2. Mengisi update Progress Report + foto bukti
3. Menginput Traffic harian (offline)
4. Memotret bukti realisasi (Loyalty, VM, Creative, Sponsor)
5. **Mengisi nilai Appraisal** (KPI + Kompetensi) dan meneruskan ke penilai berikutnya
6. Mengisi kuesioner TNA & survei EEI
7. Mengajukan perubahan data pribadi (ESS)

**Yang PASTI tidak masuk app:** seluruh Admin/master data, **Parkir Revenue**, semua CRUD berat (program Loyalty/Sponsorship, grid & import Traffic, **input Stock**, setup template & periode Appraisal, setup TNA/EEI/Competencies), upload dokumen Legal berversi, editing Content/Rundown/Creative, dan semua cetak laporan bulanan.

> **Catatan atas keputusan 2 Agustus 2026:** pengisian nilai Appraisal masuk app. Ini item **paling berat** di seluruh scope karena formnya panjang (KPI + Kompetensi, berjenjang antar penilai) dan punya aturan mode (`input` / `review` / `hr`) yang menentukan bagian mana yang boleh diubah siapa. Perlu rancangan mobile tersendiri — **per-aspek satu layar dengan progress**, bukan meniru form web yang panjang. Dijadwalkan di Fase 4, bukan Fase 1.

---

## 4. Fase Pengembangan

### Fase 0 — Pengerasan Backend *(prasyarat mutlak)*
Tanpa ini app tidak boleh dirilis ke user.

1. **Samakan otorisasi API dengan web** — muat `user_menu_access` secara additive di `BaseApiController`, tambah `canApproveMenu()`. Idealnya: ekstrak aturan ke satu trait/service yang dipakai `BaseController` **dan** `BaseApiController`, supaya tidak ada lagi dua salinan aturan yang bisa berbeda.
2. **Perkaya `/api/auth/me`** — kembalikan `role_perms`, peta menu efektif (view/edit/approve per menu), dan `employee_id`. App menyusun menu dari sini.
3. **Force-logout di API** — tolak token bila `users.is_active = 0` atau `perms_changed_at` lebih baru dari `api_tokens.created_at`.
4. **`NotificationService` terpusat** — satu titik yang mengirim **email + push (FCM) + tulis tabel `notifications`** sekaligus. Saat ini `Notify::send()` sudah menangani in-app; tinggal ditambah kanal push & email di belakang antarmuka yang sama, lalu seluruh trigger yang ada otomatis ikut jadi push. **Ini investasi paling bernilai di seluruh rencana.**
5. **Endpoint Kotak Persetujuan** — `GET /api/approvals` yang membungkus `ApprovalInbox::collect(contextForUser($id))`. Struktur hasilnya sudah datar dan siap pakai; pekerjaannya tinggal membungkus.

*Perkiraan kasar: 1,5–2 minggu.*

### Fase 1 — Hub Approval + Push *(inti nilai app)*
- Login, simpan token, refresh hak akses dari `/api/auth/me`
- Kotak Persetujuan terpadu + detail per modul + aksi Setujui/Tolak beserta alasan
- Push notification masuk → ketuk → langsung ke item
- Beranda ringkas: jumlah tunggakan + KPI seadanya

*Perkiraan kasar: 3–4 minggu.* **Setelah fase ini app sudah layak dipakai** — sisanya penambah nilai.

### Fase 2 — Capture Lapangan *(alasan kedua bikin native)*
- Input Traffic harian: per pintu, per mall, stepper angka, **antrian offline + idempotency key**
- Kamera multi-foto untuk bukti (Progress Report 5 foto, Loyalty/VM/Creative/Sponsor)
- Indikator status sinkronisasi & penanganan konflik

*Perkiraan kasar: 3 minggu.*

### Fase 3 — Glance & Baca
- Dashboard + Market Intelligence
- Daftar & detail Event (6 modul wajib), Progress Report, Parkir Live, Traffic summary, Legal, Stock
- Profil/ESS + pengajuan perubahan data

*Perkiraan kasar: 3–4 minggu.*

### Fase 4 — Pelengkap
- **Pengisian nilai Appraisal** — layar per-aspek dengan progress, hormati mode `input`/`review`/`hr`, teruskan ke penilai berikutnya, pendapat karyawan
- Isi TNA & EEI dari app
- Talent Portfolio & Competencies (lihat)
- Penghalusan: mode gelap/terang, aksesibilitas, tuning offline

*Perkiraan kasar: 3–4 minggu* (naik dari 2 minggu karena scoring Appraisal masuk).

> Estimasi di atas untuk **satu developer Flutter penuh waktu**, di luar QA dan proses rilis ke store. Fase 0 dikerjakan di sisi backend dan bisa berjalan paralel dengan penyiapan proyek Flutter.

---

## 5. Endpoint yang Perlu Ditambah

| Endpoint | Fase | Catatan |
|---|---|---|
| `GET /api/auth/me` *(diperkaya)* | 0 | + `role_perms`, peta menu efektif, `employee_id` |
| `GET /api/approvals` | 0 | Bungkus `ApprovalInbox::collect()` — sudah siap |
| `POST /api/approvals/{modul}/{id}/decide` | 1 | Satu jalur untuk semua modul: `{aksi: approve\|reject, alasan}` |
| `GET /api/notifications`, `POST /api/notifications/read` | 1 | Cermin controller web `Notifications` |
| `GET /api/work-report`, `POST /api/work-report/{id}/update` | 2 | Termasuk unggah foto multipart |
| `POST /api/traffic` | 2 | Wajib idempotency key untuk antrian offline |
| `POST /api/uploads/photo` | 2 | Lewat `ImageCompressor` yang sudah ada |
| `GET /api/dashboard/summary` *(diperluas)* | 3 | + Market Intelligence |
| `GET /api/events/{id}/{modul}` | 3 | Baca 6 modul wajib per event |
| `GET /api/parking/live`, `/api/traffic/summary`, `/api/legal`, `/api/stock` | 3 | Read-only |
| `GET/POST /api/tna`, `/api/eei` | 4 | Pengisian kuesioner |
| `GET /api/appraisal/forms/{id}` | 4 | Detail form + mode aksi user (`input`/`review`/`hr`/null) |
| `POST /api/appraisal/forms/{id}/score` | 4 | Simpan nilai KPI & Kompetensi — **wajib pakai ulang aturan `actionMode()`** agar batas kewenangan sama dengan web |
| `POST /api/appraisal/forms/{id}/forward` | 4 | Teruskan ke penilai berikutnya |

---

## 6. Risiko & Hal yang Perlu Diputuskan

| Hal | Catatan |
|---|---|
| **Dua salinan aturan akses** | `BaseController` (web) dan `BaseApiController` (API) saat ini menyalin aturan yang sama. Sudah terbukti menyimpang (grant per-user & hak Setujui belum ada di API). **Sebaiknya disatukan di Fase 0**, kalau tidak celah ini akan terulang setiap ada perubahan aturan akses. |
| **Distribusi aplikasi** | Belum diputuskan: Play Store + App Store publik, atau distribusi internal (TestFlight / APK internal)? Ini memengaruhi jadwal — review App Store bisa memakan waktu. |
| **Akun Apple Developer & Google Play** | Perlu diadakan bila memilih jalur store. Biaya tahunan + proses verifikasi perusahaan. |
| **Firebase (FCM)** | Perlu proyek Firebase + konfigurasi iOS (APNs key). Gratis untuk skala MIC. |
| **Perangkat Security untuk input Traffic** | Perlu dipastikan HP yang dipakai petugas mampu menjalankan app & punya kamera layak. |
| **Scoring Appraisal di mobile** | Sudah diputuskan masuk app. Risikonya: form panjang + aturan kewenangan berlapis. Mitigasi: layar per-aspek, simpan otomatis per langkah, dan **pakai ulang `AppraisalForm::actionMode()`** — jangan tulis ulang aturannya di API. |
| **Keputusan yang sudah final (2 Agu 2026)** | Parkir Revenue **dikecualikan seluruhnya** · Parkir Live (slot real-time) **masuk** · Pengisian nilai Appraisal **masuk app** · Stock **hanya lihat**, input tetap web. |

---

## 7. Aset yang Sudah Ada

- **Prototipe UI/UX clickable** (HTML, tema dark glassmorphism sesuai `public/css/theme.css`): login, Beranda, 7 tampilan modul, detail event, Approval + detail, sheet update Progress, sheet input Traffic, push notif, plus **simulasi hak akses** ("Lihat sebagai" Admin / GM / Dept Head / Security) yang membuktikan app menyusut mengikuti permission.
  URL: `https://claude.ai/code/artifact/75dbbc21-538c-497a-bedb-60d9e913cdaf`
- Palet: bg `#030911`, aksen crimson `#e8415a` → pink `#f8829a`, sekunder cyan `#22d3ee`; kode mall eWalk `#3b82f6`, Pentacity `#10b981`. Font mengikuti sistem perangkat agar terasa native.

**Langkah berikutnya yang disarankan:** kerjakan **Fase 0** lebih dulu — nilainya langsung terasa di web juga (aturan akses jadi satu sumber, notifikasi jadi satu jalur), dan tanpa itu app tidak bisa dipercaya.

---

*Disusun oleh IT Department — PT. Wulandari Bangun Laksana Tbk.*
