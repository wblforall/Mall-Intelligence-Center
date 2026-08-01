# MIC Mobile — Desain Aplikasi

Status: **desain** (2026-07-25). Belum dikoding.
Prototipe UI/UX (klik-able): https://claude.ai/code/artifact/75dbbc21-538c-497a-bedb-60d9e913cdaf

Companion mobile untuk Mall Intelligence Center. **Bukan porting MIC** — shell native tipis di atas API yang sudah ada, fokus pada tiga hal yang benar-benar butuh native. Sisanya tetap di web/PWA.

---

## 1. Tujuan & prinsip

MIC sudah installable sebagai PWA + responsif (`table-cardify`). Aplikasi native **hanya dibenarkan untuk 3 hal** yang tak bisa ditutup PWA (khususnya iOS):

1. **Push notification andal** — approval & pengingat.
2. **Antrian offline** untuk input lapangan (sinyal mall tidak stabil).
3. **Kamera cepat multi-foto** untuk foto bukti (volume tinggi).

Prinsip:
- **Reuse, jangan bikin ulang.** Backend CI4 + MySQL yang sama; tambah endpoint pada grup `api` yang sudah hidup.
- **Otorisasi = identik web.** Pakai ulang `canViewMenu()/canEditMenu()` + `role_perms`. App tidak boleh jadi pintu belakang.
- **Tesis produk:** app = **inbox approval + notifikasi + capture lapangan**, bukan mini-MIC.

---

## 2. Ruang lingkup — pemetaan per modul per fitur

Kacamata: kebutuhan data **level manager** di HP hanya 4 jenis — (1) denyut harian, (2) antrian keputusan, (3) kesehatan program, (4) sinyal bahaya. Menyusun data / form panjang / analisa dalam / cetak = kerja meja (web). Pengecualian input di app hanya **capture lapangan** (foto & angka pintu) — persona staf, bukan manager.

Legenda: ✅ view · ⚡ aksi · 🔔 push · ❌ web-only. *(Keputusan final bersama user, 2026-07-25.)*

| Modul | Masuk mobile | Tetap web |
|---|---|---|
| **Dashboard** | ✅ KPI operasional (traffic/parkir/event/pending), cuaca, "hari ini di mall", **Market Intelligence (termasuk)** | — |
| **Events** | ✅ list+detail+status, kelengkapan 6 modul (+🔔 lewat end_date belum lengkap), budget vs realisasi+ROI, rundown hari ini, galeri · ⚡ approve/reject event (GM) | susun content/rundown/budget/evaluasi, entry exhibitor/sponsor/VM, laporan post event |
| **Traffic** | ✅ hari ini + tren 7 hari + per mall/pintu · 🔔 rekap harian (paralel `traffic-summary-email`) · ⚡ input/revisi per pintu (**Security**, offline, window 3 hari) | compare mendalam, import Excel, laporan bulanan |
| **Parkir/SPI** | ✅ volume kendaraan + okupansi — **TANPA pendapatan (Rp tidak tampil di mobile)** | pendapatan/revenue, rekonsiliasi, sync manual |
| **Loyalty + Stock** | ✅ program aktif+capaian+status, summary bulanan, **view stok barang & voucher (level stok/menipis)** · ⚡ foto bukti realisasi | CRUD program/tenant/hadiah/voucher, mutasi stok & import kode, closing eval, laporan |
| **Sponsorship** | ✅ pipeline deal (prospek→lunas) + capaian vs target · 🔔 deal berubah status / realisasi masuk | entry program/deal/realisasi/analisa, laporan |
| **Creative & Media Promo** | ⚡ approval request media (single/batch — **API sudah ada**) · ✅ status request dept saya · ⚡ foto bukti terpasang | request baru (spot+slot), upload materi (PSD/AI), Gantt, master spot |
| **Progress Report** ⭐ | **LENGKAP di mobile**: ✅ dashboard rekap scoped, list+thread komentar · ⚡ buat/edit inisiatif, update % + catatan + foto (5), komentar/balas/flag/note GM, arsip | laporan print |
| **HR / Appraisal / ESS** | ⚡ approve pengajuan data & dokumen, approve/reject template appraisal, forward/finalize form · ✅ HR dashboard ringkas | scoring appraisal penuh (v1), data karyawan CRUD, struktur org. ESS karyawan = fase lanjut (pendorong adopsi massal) |
| **People Dev** | ⚡ approve IDP & PIP (**API sudah ada**) · 🔔 reminder review PIP H-1 · ⚡ **isi survey TNA & EEI** (form survey cocok di HP, semua karyawan) | setup TNA/EEI (dimensi/pertanyaan), training/kompetensi, matriks 9-box |
| **Legal** | 🔔 expiry kontrak/izin H-30/H-7 (`legal:check-expiry` sudah ada) · ⚡ review: komentar, request-revision, mark-final/signed (v1.1, butuh PDF viewer) | CRUD 7 jenis dokumen + upload versi |
| **Gantt, Admin, Logs** | — | semuanya (layar lebar / sensitif / kerja duduk) |

**Kanban/Boards** (KANBAN_DESIGN.md, belum dikoding): **tidak digabung** ke proyek app — dibangun di web dulu, jalur terpisah. Integrasi kelak via `NotificationService` (mention/penugasan/due date → push) + view "kartu saya" di app v2.

### Status API per sumber approval
Sudah ada: Media Promo, IDP, PIP (`app/Controllers/Api/`). Perlu ditambah: Event, Appraisal, ESS (data/dokumen), Legal review, Progress Report.

---

## 3. Arsitektur

```
Flutter app ──HTTPS Bearer token──►  MIC CI4 (grup /api)  ──►  MySQL (sama)
    │  FCM push  ◄──────────────────  NotificationService  ──►  EmailNotifier
    │  SQLite/Hive (antrian offline + foto)
```

- **Backend:** 1 codebase CI4 + MySQL yang ada. Auth token stateless sudah ada (`app/Controllers/Api/BaseApiController.php`, tabel `api_tokens`, `ApiTokenModel`); CSRF sudah dikecualikan untuk `api/*` (`app/Config/Filters.php`).
- **App:** Flutter (konsisten dengan rencana Security Patrol). State lokal SQLite/Hive untuk antrian offline & cache.
- **Push:** FCM. `api_tokens.push_token` sudah ada (migrasi `2026-05-28-000001_AddPushTokenToApiTokens`), diisi via `POST /api/auth/push-token`. Tinggal engine pengirim.
- **Kompresi foto:** server sudah punya `App\Libraries\ImageCompressor` (resize 1600px, PNG/WebP→JPEG). App tetap kompres/strip EXIF sebelum upload demi kuota.

---

## 4. Otorisasi — reuse + 4 celah yang WAJIB ditutup

App menampilkan/menyembunyikan modul & tombol dengan aturan identik web:
`admin bypass → user_menus (grant per-user) → dept_menus`, lalu `role_perms` untuk izin sistem (approve, dsb). Kasus khusus **Security input-only traffic** = `can_edit && !can_view`. Sumber `menuKey` = `app/Libraries/SectionConfig.php` (`STANDALONE_MENUS`).

Celah pada API sekarang (`BaseApiController`) — harus diperbaiki **sebelum** app dibangun:

1. **`user_menus` tidak dimuat.** `loadPerms()` hanya isi `apiMenus` dari `DepartmentMenuModel::getMenuMap()`. Web juga menggabung `UserMenuModel::getMenuMap(user_id)` (additive). → **Fix:** muat & gabung `user_menus`; `canViewMenu/canEditMenu` cek `user_menus[k] OR dept_menus[k]`. Tanpa ini, user ber-grant khusus **salah ditolak**.
2. **`/api/auth/me` tak kirim kapabilitas.** → **Fix:** kembalikan `role_perms` + `dept_menus` + `user_menus` (+ daftar `menuKey` yang boleh) agar app bisa refresh hak akses kapan saja.
3. **Tak ada force-logout.** Token hidup 30 hari; web memutus sesi saat `users.perms_changed_at` berubah atau `is_active=0` (`AuthFilter`). → **Fix:** validasi `perms_changed_at`/`is_active` di `requireAuth()`; invalidasi token seperti `revokeAllForUser()` saat akses diubah.
4. **Belum ada engine push** (lihat §5).

**Endpoint config (baru):** `GET /api/config/menus` mengekspos `SectionConfig::STANDALONE_MENUS` agar app tak hardcode daftar modul.

---

## 5. NotificationService (investasi backend #1)

Sekarang notifikasi berserakan sebagai panggilan email di tiap controller (`app/Libraries/EmailNotifier.php`, 7 method). Ganti dengan **satu service terpusat** yang untuk satu event melakukan **3 hal**: kirim email + kirim push (FCM baca `push_token`) + tulis ke tabel `notifications` (in-app center). Sekali dibuat, semua trigger otomatis jadi push + notification center via satu jalur.

**Tabel baru `notifications`:**

| kolom | tipe | ket |
|---|---|---|
| id | PK | |
| user_id | FK users | penerima |
| type | varchar | approval / reminder / comment / result |
| module | varchar | media_promo / appraisal / legal / event / work_report / hr / traffic |
| title | varchar | |
| body | text | |
| link_type, link_id | | deep-link ke item (mis. `pip` #12) |
| is_read | bool | |
| created_at | datetime | |

**Titik trigger → jadi push + notif** (semua sudah ada sebagai email/aksi):
- **Approval masuk:** PIP/IDP diajukan (`PeoplePip`/`PeopleIdp`), Media Promo pending (`PromoMediaCtrl`), Event diajukan (`Events`), template/form Appraisal (`AppraisalTemplate`/`AppraisalForm`), perubahan/dokumen ESS (`PeopleEmployees`), review Legal.
- **Hasil balik ke pengaju:** approve/reject dengan `rejection_reason`/`catatan`.
- **Reminder cron (sudah otomatis):** `mic:pip-review-reminder` (H-1), `mic:work-report-reminder` (Senin), `legal:check-expiry` (≤30 hari), `mic:traffic-summary-email`.
- **Komentar/flag Progress Report** (kini TANPA notifikasi — paling rawan terlewat): `WorkReportCtrl::addComment`, `WorkReportDeputyCtrl` (comment/replyGm/flag).

**Endpoint:** `GET /api/notifications` (list + unread count), `POST /api/notifications/(:num)/read`, `POST /api/notifications/read-all`.

---

## 6. Spesifikasi endpoint

### Sudah ada
```
POST /api/auth/login          POST /api/auth/logout        GET  /api/auth/me      (perlu diperkaya, §4.2)
POST /api/auth/push-token
GET  /api/dashboard/summary
GET  /api/events              GET  /api/events/(:num)
GET  /api/media-promo/approvals   POST /api/media-promo/(:num)/approve|reject
GET  /api/idp/approvals  GET /api/idp/(:num)  POST /api/idp/(:num)/approve|reject
GET  /api/pip/approvals  GET /api/pip/(:num)  POST /api/pip/(:num)/approve|reject
```

### Perlu ditambah
```
# Hub approval terpadu (agregasi semua sumber → 1 inbox)
GET  /api/approvals                      # gabungan pending lintas modul utk user (type, module, urgency, link)
GET  /api/approvals/count                # angka badge

# Approval per modul (mengikuti aksi web yang sudah ada)
GET/POST /api/events/(:num)/approve|reject
GET  /api/appraisal/approvals
POST /api/appraisal/templates/(:num)/approve|reject
POST /api/appraisal/forms/(:num)/forward|finalize|release
GET  /api/hr/requests                    # perubahan data + dokumen ESS pending
POST /api/hr/change/(:num)/approve|reject
POST /api/hr/document/(:num)/approve|reject
GET  /api/legal/reviews                  POST /api/legal/reviews/(:num)/comment|request-revision|mark-final|mark-signed
GET  /api/work-report/inbox              POST /api/work-report/(:num)/comment  POST /api/work-report/(:num)/flag

# Capture lapangan
GET  /api/traffic/sheet?date=&mall=      # sel per pintu (window 3 hari, hormati can_edit)
POST /api/traffic/cell                   # simpan/revisi 1 sel; idempotency-key header
POST /api/work-report                    # buat inisiatif (Progress Report LENGKAP di mobile)
POST /api/work-report/(:num)/update      # progress % + catatan + foto (multipart, maks 5)
POST /api/work-report/(:num)/archive|restore

# Survey (People Dev)
GET/POST /api/tna/fill                   # isi assessment TNA
GET/POST /api/eei/survey                 # isi survey EEI

# Read
GET  /api/parking/summary                # volume + okupansi SAJA — tanpa pendapatan (Rp)
GET  /api/traffic/summary   GET /api/work-report/dashboard
GET  /api/loyalty/summary   GET /api/loyalty/stock      # level stok barang & voucher
GET  /api/sponsorship/pipeline
# dashboard/summary diperluas: + market intelligence

# Notifikasi (§5) + config (§4)
GET  /api/notifications   POST /api/notifications/(:num)/read   POST /api/notifications/read-all
GET  /api/config/menus
```

Setiap endpoint WAJIB `requireAuth()` lalu `canViewMenu/canEditMenu/can()` → `forbidden()` bila tidak berhak. Pola sudah ada di `IdpController`/`PromoMediaController`.

---

## 7. Perubahan data model (migrations)
- `notifications` (§5).
- `api_tokens`: tambah pemeriksaan `perms_changed_at` (bisa via join ke `users`, tanpa kolom baru).
- (opsional) `sync_log` / idempotency store untuk POST offline agar tak dobel.

Tidak ada perubahan pada tabel modul yang sudah ada — app membaca/menulis lewat endpoint di atas.

---

## 8. Offline & capture
- **Antrian offline:** POST (traffic cell, work-report update) disimpan lokal (SQLite/Hive) + **Idempotency-Key** (UUID per aksi). Retry saat online; server dedup berdasarkan key → aman dari dobel.
- **Kamera:** buka kamera belakang instan (setara `capture="environment"`), ambil beruntun, kompres client-side sebelum antre-upload.
- **Cache read:** dashboard/summary & inbox di-cache agar tetap terbaca offline (read-only).

---

## 9. Keamanan
- Token 30 hari + invalidasi saat reset password (`revokeAllForUser` sudah ada) & saat `perms_changed_at` berubah (§4.3).
- Semua otorisasi di server; UI app hanya kosmetik.
- File PII (foto/sertifikat karyawan) tetap di `WRITEPATH` + serve berauth — app akses lewat endpoint berauth, bukan URL publik.
- HTTPS wajib; `push_token` per device, dihapus saat logout.

---

## 10. Fase build

| Fase | Isi | Hasil |
|---|---|---|
| **0 — Fondasi** | Tutup 4 celah API (§4) + `NotificationService` + tabel `notifications` + engine FCM + `/config/menus` | Backend benar & aman |
| **1 — Hub Approval + Push** | `GET /api/approvals` + endpoint approve/reject Event/Appraisal/HR/Legal/Progress; app: login, inbox terpadu, detail, approve/reject, notification center, push | ROI inti — approver acc dari HP |
| **2 — Capture lapangan** | Traffic input offline + foto bukti kamera (Work Report/Loyalty); antrian + idempotency | Security & tim lapangan |
| **3 — Glance** | Dashboard, Parkir, Traffic summary, Events, Work Report (read) | Monitor sekilas |

---

## 11. Referensi kode kunci
- API: `app/Controllers/Api/*`, `BaseApiController.php` (`requireAuth`, `loadPerms`, `canViewMenu`), `app/Models/ApiTokenModel.php`, migrasi `CreateApiTokens` + `AddPushTokenToApiTokens`.
- Otorisasi web (acuan parity): `app/Controllers/BaseController.php` (`canViewMenu/canEditMenu`), `app/Models/{RoleModel,DepartmentMenuModel,UserMenuModel}.php`, `app/Libraries/SectionConfig.php`, `app/Filters/AuthFilter.php`.
- Notifikasi: `app/Libraries/EmailNotifier.php`, command di `app/Commands/*Reminder*`, `LegalCheckExpiry`, `TrafficSummaryEmail`.
- Capture: `app/Controllers/Traffic.php` (`saveCell`, `INPUT_WINDOW_DAYS`), `WorkReportCtrl`, `LoyaltyCtrl`, `app/Libraries/ImageCompressor.php`.
- PWA (yang sudah ada): `public/sw.js`, `public/manifest.webmanifest`, `public/offline.html`, `public/css/theme.css` (identitas visual + `table-cardify`).
