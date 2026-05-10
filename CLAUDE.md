# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Mall Intelligence Center — sistem manajemen event untuk dual-mall PT. Wulandari Bangun Laksana Tbk. Versi saat ini: **v1.3** (Mei 2026).

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

Belum ada integrasi aktif di v1.1.
