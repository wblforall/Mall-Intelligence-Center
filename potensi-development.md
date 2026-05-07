# Potensi Development

> Diurutkan berdasarkan prioritas pengerjaan — item di atas harus selesai sebelum item di bawahnya bisa dibangun.

---

## 1. VM Standalone (luar event)

**Kenapa duluan:** Paling simpel, dan datanya dibutuhkan oleh modul Budgeting (no. 4).

**Yang dibangun:**
- Halaman list item VM non-event
- CRUD item (nama item, deskripsi, budget)
- Input realisasi per item (tanggal, jumlah, keterangan)

**Teknis:** Tambah kolom `event_id nullable` di tabel `event_vm` dan `event_vm_realisasi` — kalau null berarti standalone. Tidak perlu tabel baru.

---

## 2. Content Standalone (luar event)

**Kenapa urutan ini:** Lebih simpel dari Creative, strukturnya mirip VM.

**Yang dibangun:**
- Halaman list content item non-event
- CRUD item (nama, tipe: program/biaya, jenis, tanggal, lokasi, PIC)
- Input realisasi per item

**Teknis:** Sama — `event_id nullable` di tabel content yang sudah ada.

---

## 3. Creative Standalone (luar event)

**Kenapa paling akhir dari ketiganya:** Paling kompleks karena ada upload file + insight digital.

**Yang dibangun:**
- Halaman list creative item non-event
- CRUD item (nama, tipe: master design/digital/cetak/influencer, platform, status)
- Input realisasi + insight (khusus digital)
- Upload file per item

**Teknis:** Sama — `event_id nullable`. Ini yang paling kompleks dari tiga standalone.

---

## 4. Modul Budgeting per Department

**Kenapa harus setelah no. 1–3:** Sumber realisasi untuk mapping budgeting mencakup data dari standalone (luar event) — kalau standalone belum ada, data budgeting tidak lengkap.

**Workflow:**

### Phase 1 — Setup (awal tahun)
- Admin/Dept Head buat **master akun** per dept (contoh VM: "Bahan Dekorasi", "Jasa Pasang", "Cetak Banner")
- Input **rencana budget per bulan** per akun untuk satu tahun penuh

### Phase 2 — Realisasi (sepanjang tahun)
Sumber data realisasi per dept ada tiga:

**1. Dari event** — mapping realisasi modul yang sudah ada:
- VM dept → `event_vm_realisasi`
- Loyalty dept → voucher terpakai + hadiah dibagikan
- Event & Promo → `event_content_realisasi` + `event_creative_realisasi`

**2. Dari standalone** — mapping realisasi modul non-event (setelah no. 1–3 selesai):
- VM standalone, Content standalone, Creative standalone

**3. Entry langsung (manual)** — dept input langsung tanpa terikat event atau modul manapun:
- Form simpel: tanggal + keterangan + jumlah → pilih akun
- Cocok untuk: biaya rapat, transportasi, ATK, dan pengeluaran lain di luar modul
- Tidak butuh tabel baru — cukup `source_type = 'manual'` di `budget_realisasi_mapping`

### Phase 3 — Mapping
- Dept buka halaman Budgeting → lihat daftar realisasi (belum di-mapping vs sudah)
- Klik item realisasi → pilih akun + bulan → simpan

### Phase 4 — Laporan

Tabel horizontal — per akun, per bulan berdampingan (Rencana | Realisasi | Selisih), kolom Total di kanan, baris Total di bawah:

```
┌──────────────┬─────────────────────────┬─────────────────────────┬─────────────────────────┐
│              │         Januari          │         Februari         │          TOTAL           │
│ Akun         ├────────┬────────┬────────┼────────┬────────┬────────┼────────┬────────┬────────┤
│              │Rencana │Real.   │Selisih │Rencana │Real.   │Selisih │Rencana │Real.   │Selisih │
├──────────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
│ Bahan Deko   │  5jt   │  4.5jt │ +500rb │  4jt   │  4.2jt │ -200rb │  50jt  │  45jt  │  +5jt  │
│ Jasa Pasang  │  2jt   │  2.1jt │ -100rb │  2jt   │  1.9jt │ +100rb │  24jt  │  23jt  │  +1jt  │
│ Belum mapping│   —    │  800rb │   ⚠   │   —    │  400rb │   ⚠   │   —    │  1.2jt │   ⚠   │
├──────────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┼────────┤
│ TOTAL        │  8.5jt │  7.8jt │ +700rb │  7.5jt │  6.1jt │+1.4jt  │  92jt  │  80jt  │ +12jt  │
└──────────────┴────────┴────────┴────────┴────────┴────────┴────────┴────────┴────────┴────────┘
```

- Selisih **hijau** = saving (realisasi < rencana)
- Selisih **merah** = over (realisasi > rencana)
- Baris **Belum mapping** = realisasi yang belum dikategorikan ke akun manapun

**Keputusan desain yang sudah disepakati:**
- Master akun dibuat **1x di awal tahun**, berlaku global (tidak per event)
- Budget tahunan di-input **per bulan** per akun
- Budget tahunan dept **terpisah** dari budget event (tidak saling potong)
- Revisi budget dimungkinkan selama tahun berjalan (di-track, bukan di-replace)
- Akses per dept: hanya lihat & kelola milik sendiri; admin lihat semua
- Split realisasi ke banyak akun: **skip dulu**
- Approval workflow: **skip dulu**

**Tabel baru yang dibutuhkan:**
- `budget_accounts` — master akun per dept
- `budget_plan` — rencana: dept_id, akun_id, tahun, bulan, jumlah
- `budget_revision` — revisi: dept_id, akun_id, tahun, bulan, jumlah_revisi, keterangan
- `budget_realisasi_mapping` — mapping: source_type, source_id, akun_id, bulan, jumlah

**Kompleksitas:** Medium — tabel laporan perlu scroll horizontal (12 bulan × 3 kolom = 36 kolom + Total).

---

### Integrasi Data Eksternal ke Modul Budgeting

Dengan integrasi ini, mayoritas realisasi masuk **otomatis** — entry manual hanya untuk sisanya:

```
CLARA (revenue)              →
Purchasing (pengeluaran)     →   Budget Intelligence MIC
Event realisasi (internal)   →
Manual entry (sisanya)       →
```

#### Integrasi CLARA (Revenue Tenant)
- **Status:** Siap diintegrasikan — server sama, database MySQL
- **Pendekatan:** Query langsung lintas database (tidak perlu API)
  ```sql
  SELECT * FROM clara_db.tabel_revenue WHERE periode = '2026-05'
  ```
- **Yang diatur:** User MySQL MIC diberi akses **read-only** ke tabel tertentu di DB CLARA
- **Kompleksitas:** Rendah
- **Prasyarat:** Koordinasi dengan tim CLARA untuk menentukan tabel/view yang boleh diakses

#### Integrasi Sistem Purchasing (Pengeluaran per Dept)
- **Status:** Perlu koordinasi — beda server, database MySQL
- **Pendekatan:** REST API read-only yang dibuatkan oleh tim Purchasing khusus untuk MIC
  ```
  Server MIC  →  request  →  Server Purchasing
              ←  response ←  (endpoint khusus MIC)
  ```
- **Kenapa API, bukan query langsung:** Beda server — membuka port MySQL langsung ke luar lebih berisiko dari sisi keamanan
- **Kompleksitas:** Medium
- **Prasyarat:** Koordinasi dengan tim teknis Purchasing untuk dokumentasi + endpoint

#### Perbandingan

| | CLARA | Purchasing |
|--|-------|------------|
| Server | Sama dengan MIC | Beda server |
| Pendekatan | Query lintas DB | REST API |
| Kompleksitas | Rendah | Medium |
| Keamanan | Read-only MySQL user | Lebih aman (API) |
| Prasyarat | Koordinasi akses DB | Koordinasi + buat endpoint |

---

## 5. Modul Tenant Relation — Service Charge & Promotion Levy

**Kenapa urutan ini:** Independen dari modul lain, tapi datanya dibutuhkan oleh AI insight (no. 7) sebagai komponen revenue mall. Menggantikan pencatatan manual Excel Tenant Relation.

**Ide:** Modul pencatatan invoice service charge dan promotion levy per tenant per mall, lengkap dengan status pembayaran.

**Struktur data per invoice:**
- Tenant, mall, tipe (service charge / promotion levy)
- Nomor invoice, tanggal invoice
- Jumlah tagihan
- Status: **Belum Bayar / Sebagian / Lunas**
- Kalau sebagian → catat jumlah yang sudah dibayar

**Tampilan:**
```
Per Mall, Per Bulan — filter: semua / belum bayar / sebagian / lunas

┌──────────┬──────────────────┬───────────┬──────────┬───────────┬──────────┐
│ Tenant   │ Tipe             │ No. Inv   │ Tgl      │ Jumlah    │ Status   │
├──────────┼──────────────────┼───────────┼──────────┼───────────┼──────────┤
│ Tenant A │ Service Charge   │ INV-001   │ 01/05    │ Rp 5jt    │ Lunas    │
│ Tenant A │ Promotion Levy   │ INV-002   │ 01/05    │ Rp 2jt    │ Sebagian │
│ Tenant B │ Service Charge   │ INV-003   │ 01/05    │ Rp 3jt    │ Belum    │
├──────────┼──────────────────┼───────────┼──────────┼───────────┼──────────┤
│ TOTAL    │                  │           │          │ Rp 10jt   │          │
│ Terkumpul│                  │           │          │ Rp 6.5jt  │          │
│ Outstanding│                │           │          │ Rp 3.5jt  │          │
└──────────┴──────────────────┴───────────┴──────────┴───────────┴──────────┘
```

**Data ini otomatis masuk ke:**
- Laporan revenue mall (manajemen)
- Konteks AI insight — gambaran kesehatan finansial mall secara keseluruhan

**Tabel baru:**
- `tenants` — master tenant per mall (atau sinkron dari CLARA jika tersedia)
- `tenant_invoices` — invoice: tenant_id, mall, tipe, no_invoice, tanggal, jumlah, status, jumlah_bayar

**Kompleksitas:** Rendah-Medium.

---

## 6. Input Pengunjung Event per Lokasi Event

**Kenapa urutan ini:** Fitur independen, tidak memblokir yang lain. Lebih ke nice-to-have.

**Ide:** Input jumlah pengunjung yang datang ke area event spesifik (bukan traffic pintu masuk mall).

**Manfaat:**
- Mengukur efektivitas event (berapa % pengunjung mall yang mampir ke area event)
- Membandingkan traffic per lokasi jika event tersebar di beberapa titik

**Pertimbangan:**
- Input manual tambahan per hari per lokasi event
- Tabel baru: `event_location_traffic` (event_id, lokasi, tanggal, jumlah)
- Paling relevan untuk event yang punya titik lokasi spesifik di dalam mall

---

## 7. Dashboard Insight Ekonomi

**Kenapa paling akhir:** Harus menunggu data internal terakumulasi 3–6 bulan setelah modul budgeting dan standalone selesai. Tanpa data internal yang solid, insight ekonomi tidak punya konteks dan hasilnya generik.

**Ide:** Dashboard indikator ekonomi eksternal yang digabung dengan data performa mall untuk menghasilkan narasi insight otomatis via Claude API.

**Indikator yang ditarik:**
| Indikator | Sumber | Frekuensi |
|-----------|--------|-----------|
| Inflasi Balikpapan | BPS Kaltim | Bulanan |
| Inflasi nasional | BPS Indonesia | Bulanan |
| Indeks Keyakinan Konsumen | Bank Indonesia | Bulanan |
| Harga BBM | Pertamina | Real-time |
| Kurs USD/IDR | Bank Indonesia | Real-time |
| Harga komoditas (batubara, CPO) | Sumber terbuka | Periodik |
| Berita ekonomi terbaru | RSS feed (Kontan, Detik Finance, Bisnis.com, Kompas Ekonomi, Tribun Kaltim) | Real-time |

**Catatan RSS feed:**
- Difilter berdasarkan relevansi (kata kunci: Balikpapan, Kaltim, ekonomi, daya beli, retail, mall) sebelum dikirim ke Claude API
- Dibatasi 10–15 headline + ringkasan per run supaya token tidak membengkak
- Bersifat kualitatif — menangkap kejadian spesifik (kebijakan baru, tren sosial, gejolak harga) yang tidak muncul di data statistik terstruktur
- Tidak menggantikan data angka dari BPS/BI, melengkapi konteks narasi

**Contoh insight yang dihasilkan (setelah data internal ada):**
> *"Inflasi Balikpapan naik 0.4%, tapi traffic event bulan ini justru +12% dibanding bulan lalu. Revenue exhibition tetap stabil. Daya beli segmen target mall tampaknya belum terpengaruh."*

**Yang tidak dibangun:**
- Forecasting/prediksi 6 bulan ke depan secara statistik — butuh model ML dan data historis panjang
- Data BPS tidak real-time, selalu terlambat 1–2 bulan — bukan limitasi sistem tapi limitasi sumber

**Prasyarat sebelum mulai:**
- Modul budgeting selesai dan sudah berjalan minimal 3–6 bulan
- Data traffic, revenue, dan realisasi sudah terakumulasi cukup untuk dibandingkan

**Cara kerja AI insight:**

```
Cron job (mingguan/bulanan)
    │
    ▼
Ambil data internal dari DB
(traffic, revenue, budget vs realisasi per dept)
+ Ambil data eksternal dari API BPS/BI/Pertamina
    │
    ▼
Kirim ke Claude API sebagai konteks
    │
    ▼
Simpan hasil narasi ke DB
    │
    ▼
Tampilkan di dashboard insight
(dengan tanggal generate + tombol "Refresh")
```

**Contoh output insight:**
> *"Bulan April, traffic pengunjung eWalk turun 8% dibanding Maret, bersamaan dengan kenaikan inflasi Balikpapan 0.6%. Revenue exhibition masih stabil di Rp 45jt, namun budget realisasi VM sudah di 87% padahal baru pertengahan tahun. Disarankan event Mei–Juni fokus pada program loyalty dengan threshold rendah untuk menjaga kunjungan di tengah tekanan daya beli."*

**Variasi insight per audiens:**
- **Manajemen** — high level: performa keseluruhan vs kondisi ekonomi
- **Event Planning** — operasional: rekomendasi tema/program event ke depan
- **Per Dept** — budget awareness: posisi realisasi vs rencana + konteks eksternal

**Insight per mall + kolaborasi:**

AI menganalisis eWalk dan Pentacity secara terpisah, lalu memberikan saran kolaborasi jika relevan:

```
Data internal eWalk          Data internal Pentacity
(traffic, event history,     (traffic, event history,
revenue, demographic)        revenue, demographic)
        │                            │
        └──────────┬─────────────────┘
                   │
        Data eksternal Balikpapan
        (ekonomi, tren, kompetitor,
         kalender nasional, musim)
                   │
                   ▼
              Claude API
                   │
        ┌──────────┴──────────┐
        ▼                     ▼
   Insight eWalk       Insight Pentacity
   + saran event       + saran event
   + target market     + target market
        │                     │
        └──────────┬───────────┘
                   ▼
        Saran kolaborasi event
        (kapan worth it bareng,
         kapan lebih baik sendiri)
```

Contoh output:

> **eWalk** — *"Segmen anak muda 18–28 tahun mendominasi traffic weekend. Tren nasional menunjukkan peningkatan minat F&B festival dan pop culture event. Rekomendasi: event musik indie atau food bazaar tematik."*

> **Pentacity** — *"Traffic keluarga tinggi di weekend, terutama anchor tenant area. Rekomendasi: family activity event atau kids competition."*

> **Kolaborasi** — *"Event skala besar seperti brand activation nasional atau seasonal sale lebih efektif dijalankan bersamaan di kedua mall untuk memaksimalkan jangkauan demografis Balikpapan."*

**Batasan AI:**
- Tidak prediksi angka spesifik ("traffic bulan depan X orang") — itu forecasting statistik
- Tidak real-time — dipanggil terjadwal, hasil disimpan di DB

**Teknis:** Cron job → ambil data internal per mall + API BPS/BI/Pertamina + PAM Plus API → Claude API → simpan narasi per mall + kolaborasi → tampil di dashboard.

---

### Integrasi PAM Plus (Customer Behavior)

**Status: menunggu koordinasi API dengan tim teknis PAM Plus**

**Ide:** Tarik data member PAM Plus via API untuk memperkaya analisa customer behavior di insight AI.

**Data yang bisa ditarik:**
- Demografi member (usia, gender, domisili)
- Frekuensi kunjungan per member
- Spending behavior per kategori tenant
- Respons terhadap promo/event
- Member aktif vs tidak aktif
- Distribusi tier member

**Dampak ke insight AI:**

Tanpa PAM Plus:
> *"Traffic bulan ini 12.000 orang"*

Dengan PAM Plus:
> *"Dari 12.000 pengunjung, 4.200 adalah member aktif. Segmen 25–35 tahun naik 18% saat ada event F&B. Member tier Gold spending-nya 2.3x lebih tinggi di bulan dengan event. Rekomendasi: event berikutnya targetkan aktivasi member Silver yang kunjungannya menurun 3 bulan terakhir."*

**Dampak ke modul Loyalty:** Program loyalty di sistem ini bisa dikalibrasi berdasarkan perilaku nyata member PAM Plus, bukan asumsi.

**Prasyarat:**
- Dokumentasi API PAM Plus tersedia
- Koordinasi dengan tim teknis PAM Plus selesai
- Kesepakatan scope data yang boleh ditarik (privacy/akses)
