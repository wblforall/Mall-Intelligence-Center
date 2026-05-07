# Mall Intelligence Center — Architecture & Flow

**IT Department — PT. Wulandari Bangun Laksana Tbk.**

---

## 1. Stack & Infrastruktur

| Layer | Teknologi |
|---|---|
| Framework | CodeIgniter 4 (MVC) |
| Database | MySQL (XAMPP) |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js |
| Server | Apache (XAMPP) |
| Session | File-based (CI4 default) |

Aplikasi berjalan **single-server**. Database MySQL berada di server yang sama dengan Apache.

---

## 2. Struktur Direktori Penting

```
app/
├── Controllers/        # Satu controller per modul
├── Models/             # Satu model per tabel utama
├── Views/              # Satu folder per modul
│   └── layouts/main.php    # Layout utama dengan navbar
├── Services/
│   └── EventFinanceService.php   # Agregasi budget/revenue/traffic (bulk query)
├── Libraries/
│   └── ActivityLog.php           # Logging aktivitas user
└── Filters/
    └── AuthFilter.php            # Guard semua route kecuali login

public/uploads/         # File upload per modul, dipisah per event_id
```

---

## 3. Modul & Tabel Database

```
events (tabel pusat)
│
├── event_budgets              ← Budget dept per event
├── event_exhibitors           ← Exhibition / Casual Leasing
│   └── event_exhibitor_programs
├── event_sponsors             ← Sponsorship
│   ├── event_sponsor_items    ← Detail item in-kind
│   └── event_sponsor_realisasi
├── event_loyalty_programs     ← Program Loyalty per event
│   ├── event_loyalty_voucher_items
│   │   └── event_loyalty_voucher_realisasi
│   ├── event_loyalty_hadiah_items
│   │   └── event_loyalty_hadiah_realisasi
│   └── event_loyalty_realisasi    ← Data member
├── event_vm_items             ← Visual Merchandising
│   └── event_vm_realisasi
├── event_content_items        ← Content & Program Acara
│   ├── event_content_realisasi
│   └── event_rundown          ← Auto-sync dari content items tipe 'program'
├── event_creative_items       ← Creative & Design
│   ├── event_creative_files
│   ├── event_creative_realisasi
│   └── event_creative_insights    ← Metrik digital (reach, views, dll)
├── event_locations            ← Titik lokasi dalam mall per event
├── event_completions          ← Status penyelesaian data per modul
└── event_daily_tracking       ← Tracking harian opsional

Tabel independen (tidak terikat event_id langsung):
├── daily_traffic              ← Traffic per pintu, per tanggal, per mall
├── daily_vehicles             ← Kendaraan per tanggal
└── loyalty_programs           ← Program Loyalty standalone (bukan per event)
    ├── loyalty_voucher_items
    └── loyalty_hadiah_items
```

---

## 4. Lifecycle Event

Status event dihitung **otomatis** oleh `EventModel::calcStatus()` berdasarkan tanggal hari ini dan data completion. Tidak ada kolom `status` yang ditulis manual.

```
today < start_date
        │
        ▼
     [ DRAFT ]
        │
        │  today >= start_date
        ▼
     [ ACTIVE ]   ←── event sedang berjalan
        │
        │  today > end_date
        ▼
  [ WAITING DATA ]  ←── event selesai, ada modul belum di-mark complete
        │
        │  semua REQUIRED_MODULES sudah complete
        ▼
    [ COMPLETED ]
```

**Required Modules** (dari `EventCompletionModel::REQUIRED_MODULES`):

| Key | Label |
|---|---|
| `content` | Content & Rundown |
| `loyalty` | Program Loyalty |
| `vm` | Dekorasi & VM |
| `creative` | Creative & Design |
| `exhibitors` | Exhibitor |
| `sponsors` | Sponsor |

Modul dianggap complete ketika user menekan tombol "Tandai Selesai" di halaman masing-masing modul. Data completion disimpan di tabel `event_completions`.

---

## 5. Mekanisme `canEdit`

Setiap halaman modul mengirim `canEdit` ke view. Flag ini dikontrol oleh dua kondisi:

```
canEdit = canEditMenu(menuKey) AND completion == null
```

- `canEditMenu()` — cek permission role/dept dari session
- `completion == null` — modul belum ditandai selesai

Artinya: **begitu modul di-mark complete, seluruh form input di halaman itu otomatis terkunci** (readonly/hidden). Admin dapat membuka kembali dengan menghapus completion record.

---

## 6. Sistem Akses (Role & Departemen)

```
Session setelah login:
├── user_id, name, role ('admin' | 'user')
├── dept_id, dept_name
├── dept_menus   → { menu_key: { can_view, can_edit, section_type } }
└── role_perms   → { can_view_xxx: bool, can_input_xxx: bool }
```

**Prioritas akses:**
1. `role = 'admin'` → bypass semua, akses penuh
2. `role_perms` → permission berbasis role (override dept_menus)
3. `dept_menus` → permission berbasis departemen

`canViewMenu(key)` / `canEditMenu(key)` dipanggil di setiap controller. Jika false → redirect ke `/events` dengan pesan error.

---

## 7. Flow Data ke Summary Event

Halaman `summary/index` mengumpulkan data dari semua modul untuk satu event. Semua kalkulasi dilakukan di `EventSummary::index()` sebelum dikirim ke view.

```
                         ┌─────────────────────────────┐
                         │     SUMMARY EVENT (index)   │
                         └────────────┬────────────────┘
                                      │
              ┌───────────────────────┼───────────────────────┐
              │                       │                       │
     ┌────────▼────────┐   ┌──────────▼──────────┐  ┌────────▼────────┐
     │   TOTAL BUDGET  │   │   TOTAL REVENUE     │  │  TOTAL TRAFFIC  │
     └────────┬────────┘   └──────────┬──────────┘  └────────┬────────┘
              │                       │                       │
   ┌──────────┴──────────┐   ┌────────┴────────┐    daily_traffic
   │ EventFinanceService │   │ exhibition       │    (by start_date
   │ ::getBudgetTotal()  │   │ nilai_dealing    │    + event_days)
   └──────────┬──────────┘   │                 │
              │              │ sponsor cash     │
   ┌──────────┴────────────┐ │ nilai (jenis=    │
   │ event_budgets (dept)  │ │ 'cash')          │
   │ event_loyalty_programs│ └─────────────────┘
   │ event_vm_items        │
   │ event_content_items   │
   │ event_creative_items  │
   └───────────────────────┘

TOTAL BUDGET REALISASI:
  loyalty realisasi   = Σ (voucher terpakai × nilai) + Σ (hadiah dibagikan × nilai_satuan)
  content realisasi   = Σ nilai dari event_content_realisasi
  creative realisasi  = Σ nilai dari event_creative_realisasi
  vm realisasi        = Σ jumlah dari event_vm_realisasi
```

---

## 8. Flow Data ke Summary Bulanan

`EventSummary::monthly()` mengambil semua event dalam satu bulan lalu mengagregasi menggunakan **bulk query** (9 query total, tidak peduli berapa banyak event).

```
EventFinanceService::getBulkBudgetTotals([$id1, $id2, ...])
  → 5 query (satu per tabel budget), hasil: [event_id => total]

EventFinanceService::getBulkRevenueTotals([$id1, $id2, ...])
  → 2 query (exhibitors + sponsors cash), hasil: [event_id => total]

EventFinanceService::getBulkTrafficTotals($events)
  → 1 query rentang tanggal gabungan semua event
  → distribusi ke event dilakukan di PHP berdasarkan start_date + event_days

EventFinanceService::getBulkVehicleTotals($events)
  → 1 query rentang tanggal gabungan
  → distribusi sama seperti traffic
```

---

## 9. Mapping Traffic ke Event

Traffic disimpan per tanggal di `daily_traffic`, bukan per event. Pemetaan ke event dilakukan saat query:

```
Event A: start_date = 2026-05-01, event_days = 3
  → cover tanggal: 2026-05-01, 2026-05-02, 2026-05-03

Event B: start_date = 2026-05-05, event_days = 2
  → cover tanggal: 2026-05-05, 2026-05-06

Query traffic: WHERE tanggal BETWEEN '2026-05-01' AND '2026-05-06'
PHP: distribusikan total per tanggal ke event yang cover tanggal tersebut
```

Implikasi: jika dua event berlangsung di tanggal yang sama (berbeda mall), traffic hari itu akan terhitung di **kedua** event.

---

## 10. Sinkronisasi Content → Rundown

Saat item content bertipe `'program'` ditambah atau diedit, rundown **otomatis ter-update** via `EventRundownModel::syncFromContentItem()`. Jika tanggal dihapus dari item, entry rundown ikut dihapus.

```
addItem / editItem (tipe = 'program')
        │
        ▼
  EventRundownModel::syncFromContentItem()
        │
        ├── hitung hari_ke berdasarkan selisih tanggal item dengan start_date event
        └── upsert ke event_rundown
```

Item tipe `'biaya'` tidak masuk ke rundown.

---

## 11. Activity Log

Setiap operasi create/update/delete memanggil `ActivityLog::write()`. Log disimpan ke tabel `activity_logs` dengan field: `action`, `module`, `record_id`, `record_label`, `context` (JSON), `user_id`, `created_at`.

Tidak ada side effect — log bersifat append-only dan tidak mempengaruhi alur data lain.

---

## 12. Integrasi Eksternal

### Sudah Ada
Belum ada integrasi eksternal aktif di v1.1.

### Direncanakan

| Sistem | Tipe | Status | Catatan |
|---|---|---|---|
| CLARA (ERP internal) | MySQL cross-database query | Planned | Sama server, bisa direct query |
| Purchasing System | REST API | Planned | Server berbeda, perlu endpoint |
| PAM Plus (membership) | REST API | Pending koordinasi | Perlu API spec dari tim teknis |

Saat integrasi diimplementasikan, titik masuk yang paling logis:
- **CLARA** → modul Budget (mapping realisasi dari sistem keuangan)
- **Purchasing** → modul VM & Creative (validasi vendor/PO)
- **PAM Plus** → modul Loyalty (sync data member)

---

*Dokumen ini menggambarkan arsitektur per v1.1 — Mei 2026.*
*Perbarui bagian yang relevan setiap kali ada perubahan signifikan pada flow data atau struktur modul.*
