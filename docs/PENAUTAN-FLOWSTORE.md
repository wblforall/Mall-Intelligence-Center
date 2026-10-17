# Penautan FlowStore → Budget Departemen

Tab **FlowStore** di Budget Departemen menarik barang yang sudah **diterima** (BTB) dari FlowStore dan
membiarkan user memetakannya per item ke **pos** dan **bulan pembebanan**, lalu menekan **Catat** untuk
memasukkannya ke Realisasi. Keputusan pemilik (17 Okt 2026):

1. Pembelian = realisasi **saat barang diterima** (BTB). Di FlowStore semua BTB berstatus `draft` (artinya sudah
   diterima), jadi MIC menganggap `final` **dan** `draft` sebagai diterima; hanya `batal` yang tidak.
2. Pemetaan **tidak otomatis**. Sistem hanya merekomendasikan; user bisa mengubah dropdown pos/bulan/mal per baris.
3. **Mode usulan**: tak ada angka masuk `budget_realisasi` tanpa user menekan **Catat**.
4. **Tanggal mulai penarikan** (17 Okt 2026): FlowStore hanya ditarik untuk barang diterima **>= 1 Okt 2026**; bulan-bulan
   sebelumnya sudah diisi dari Excel dan tidak boleh masuk antrean (agar tak terhitung dobel). Lihat bagian berikutnya.
5. **Keterangan order (keperluan)**: tiap baris menampilkan *untuk kebutuhan apa* barang dibeli (dari PR/MR/PO/BTB), dipakai
   untuk mencari, mengelompokkan, merekomendasikan pos, dan ikut tercatat di realisasi.

## Tanggal mulai penarikan

Urutan sumber: baris khusus dept di `budget_fs_setelan` > baris semua dept (`dept_id = 0`) > `.env flowstore.mulai` > bawaan `2026-10-01`.
Admin mengubahnya lewat kotak **"Penarikan FlowStore mulai … — ubah"** di tab (semua dept, atau khusus dept yang dibuka; "Ikuti bawaan"
menghapus penimpaan dept).

- `mic:fs-sync` / tombol Sinkron: `dari` **selalu dipangkas** ke tanggal mulai dept pemilik divisi (bawaan `--dari` = tanggal mulai).
  Mis. `--dari 2025-12-01` tetap hanya menarik sejak 1 Okt 2026.
- Deteksi item "hilang dari feed" hanya pada rentang >= tanggal mulai.
- **Pembersihan cermin**: tiap sinkron (dan saat admin memajukan tanggal) item yang diterima SEBELUM tanggal mulai dan masih `baru`
  (tanpa realisasi) dibuang dari `fs_btb_item` + `budget_fs_pemetaan`. Item `dicatat` / `diabaikan` / `perlu_ditinjau` tidak disentuh.
  Memundurkan tanggal perlu sinkron ulang.
- Antrean, KPI, dan lencana tab hanya menghitung `tanggal_diterima >= tanggal mulai`. Aturan lama "carry-over sejak 1 Desember tahun lalu"
  **dihapus**. Tahun halaman yang seluruhnya sebelum tanggal mulai menampilkan penjelasan ramah; tahun mendatang (2027 dst.) tetap bekerja.

## Keterangan order (keperluan)

Feed (aditif, versi tetap 1) membawa per item: `keterangan_pr`, `keterangan_po`, `keterangan_btb`, `keperluan` (gabungan siap tampil,
maks 500 karakter, prioritas PR → MR → PO → BTB), `spesifikasi` (maks 500), `no_mr`. Disimpan di cermin `fs_btb_item` apa adanya
(escape saat tampil; teks kotor dari sumber tidak dibersihkan).

- **Toleran**: feed lama tanpa field ini tidak menghapus keperluan yang sudah ada di cermin; field ada tetapi kosong → dikosongkan.
- **Perubahan keterangan hanya memperbarui cermin** — tidak pernah membuat item `perlu_ditinjau` (itu khusus nilai/batal/hilang).
- **Tampilan**: di bawah nama barang, teks kecil maks 2 baris dengan "selengkapnya" (membuka teks lengkap + spesifikasi + No. MR/PR/PO/BTB
  + keterangan PR/PO/BTB yang berbeda). Tooltip memuat teks lengkap.
- **Cari**: kotak Cari juga mencari di keperluan, spesifikasi, No. MR, No. PR.
- **Chip "Keperluan"**: kelompok keperluan yang sama (>= 2 item) sebagai chip (item & total); klik = saring (`kep=`).
- **Kelompokkan menurut keperluan** (`kel=1`): baris dikelompokkan; kepala kelompok memuat keperluan, No. MR, jumlah & total, serta
  pilihan **pos + bulan** dengan tombol *Terapkan ke N item* dan *Catat kelompok*. Terapkan hanya MENGISI pilihan tiap baris (draf per item
  tersimpan seperti biasa), sehingga tiap baris tetap bisa diubah satu-satu; kotak centang kelompok memilih semua barisnya.
- **Saat Catat**: `realisasi.keterangan` = `Untuk: <keperluan> | Supplier: <nama> | MR: <no_mr>` (bagian kosong dilewati; keperluan dipotong 600
  karakter; kolom TEXT). `uraian` tetap seperti semula. Keterangan tampil di tab Realisasi (di bawah uraian) dan keperluan di panel "Sudah dicatat".

## Alur

```
FlowStore  --(feed baca-saja)-->  fs_btb_item (cermin)  -->  budget_fs_pemetaan (antrean: baru)
                                                                  | user pilih pos/bulan/mal (draf tersimpan otomatis)
                                                                  v  Catat
                                                       budget_realisasi (+ pemetaan 'dicatat' + memori rekomendasi)
```

- **Sinkron**: tombol "Sinkronkan dari FlowStore" (hak tulis) atau `php spark mic:fs-sync`. Idempoten; hanya
  mengisi cermin & antrean, **tidak pernah mengubah realisasi**.
- **Antrean** per dept (lewat pemetaan divisi) dan tahun halaman (berdasarkan `tanggal_diterima`), hanya sejak tanggal mulai penarikan.
- **Catat**: `tanggal` = tanggal diterima bila bulan beban = bulan terima, selain itu tanggal terakhir bulan beban;
  `periode_asal` = bulan terima bila berbeda; `uraian` = `<barang> ×<qty> <satuan> — BTB <no> / PO <no>`;
  `keterangan` = keperluan + supplier + No. MR (lihat "Keterangan order"); `nilai` = `nilai_diterima` dari feed; `mall` dari skema (1 Pentacity, 2 eWalk; selain itu
  keduanya) atau pilihan user; `referensi` = `FlowStore BTB <no_btb> #<id_btb_item>`. Aturan tahun server tetap berlaku
  (bulan beban harus di tahun halaman). Satu transaksi DB + kunci baris + `UNIQUE(id_btb_item)` → klik ganda/POST ulang
  tidak membuat dobel.
- **Abaikan** (wajib beralasan; bukan beban dept, sudah dicatat manual, dsb.) dan **Batalkan abaikan**.
- Menghapus realisasi hasil catat di tab Realisasi mengembalikan item ke "Belum diproses" (dan mengurangi memori).
- Baris Realisasi dari FlowStore diberi lencana **FlowStore** dengan tautan ke tab.

## Status & perubahan di FlowStore

| Kejadian di FlowStore | Item `baru`/`diabaikan` | Item `dicatat` |
|---|---|---|
| BTB menjadi `batal` | keluar dari antrean | `perlu_ditinjau` |
| Baris BTB **hilang** dari feed (dihapus) pada rentang yang disinkronkan | keluar dari antrean | `perlu_ditinjau` ("tidak lagi ada di FlowStore") |
| `nilai_diterima` berubah | nilai terbaru dipakai | `perlu_ditinjau` |

Deteksi "hilang" hanya dilakukan bila tarikan rentang itu **sukses penuh** (semua halaman); bila galat/terpotong, tak ada
yang ditandai. Realisasi tak pernah diubah otomatis; user memutuskan: hapus realisasinya, atau "Sudah ditinjau".
Nilai tidak bisa diubah di tab ini (dibaca apa adanya, sudah termasuk PPN). Nilai Rp 0 tampil tetapi tak bisa dicatat
(gunakan Abaikan). Koreksi nilai = Abaikan + input manual di tab Realisasi.

## Rekomendasi pos (`app/Libraries/BudgetFsRekomendasi.php`)

Urutan: (0) **keperluan** — keterangan order menjelaskan tujuan pembelian, lebih terang daripada nama barang (kabel UTP / switch / RJ45
untuk "PENAMBAHAN KAMERA CCTV" → pos *Penambahan CCTV*). Aturan kata kunci bersasaran `keperluan` (kolom `budget_fs_aturan.sasaran`) berlaku
**berurutan** (yang teratas cocok menang, skor 80, alasan menyebut keperluan, mis. `keperluan "penambahan kamera cctv" · nama barang sejalan`
atau `(mengalahkan nama barang → …)`); aturan tanpa pos menutup rekomendasi (mis. "LED VIDEOTRON" dan "MONITOR CCTV"). Bila tak ada aturan,
kemiripan keperluan ↔ nama pos dipakai secara **konservatif** (semua token pos >= 2 token ada di keperluan dan pemenangnya tunggal; skor 55).
Keperluan didahulukan atas memori (memori hanya mengenal nama barang, tak tahu tujuannya). Item tanpa keperluan (feed lama) memakai urutan lama:
(a) **memori** (kode_barang / nama ternormalisasi yang pernah dicatat di dept yang sama; paling sering menang),
(b) **kata kunci** per dept (dapat diedit admin; kata kunci yang muncul paling awal pada nama menang; aturan tanpa pos
mencegah rekomendasi keliru, mis. perangkat jaringan di IT), (c) **kemiripan** token nama barang ↔ nama pos, (d) tanpa
rekomendasi. Tidak bergantung pada `kategori` (hampir selalu kosong). Aturan keperluan dept IT di-seed oleh migrasi
`2026-10-17-000003_FsKeperluan` dan dapat ditambah/dihapus admin di **Pengaturan penautan** (pilih "pada keperluan"). Uji:
`php spark mic:fs-uji-rekomendasi` (52 kasus: nama barang tanpa keperluan, keperluan, memori [di-rollback], kemiripan).

## CCD (Creative Concept & Design): aturan mal dan tautan ke Creative (18 Okt 2026)

Keputusan pemilik: **FlowStore adalah SUMBER realisasi biaya CCD** (divisi FlowStore `DGM` id 35 ↔ dept 3). BTB DGM dipetakan ke pos CCD lewat tab
FlowStore seperti dept lain (usulan → Catat); biaya TIDAK diinput ulang di modul Creative. Migrasi aditif `2026-10-18-000010_CcdFlowstore`.

**Aturan rekomendasi bermal & pengganti terakhir** (`budget_fs_aturan.mal`, `.pengganti`; mesin `BudgetFsRekomendasi`):
- `mal` = `ewalk` | `pentacity` | `both` (skema FlowStore tak dikenal / kosong); NULL = semua mal. Aturan bermal hanya dipakai bila mal item sama
  (mal dari `id_skema`/`skema`: 2/Ewalk, 1/Pentacity). Dipakai untuk T-Banner: `T BANNER|X BANNER` + e-Walk → *T-Banner 1x3m (e-Walk)*, + Pentacity →
  *T-Banner 1x2,4m (Pentacity)*, + skema tak dikenal → "tanpa pos" (jangan menebak). Bila ada aturan bermal yang cocok, **memori tidak dipakai**
  (memori hanya mengenal nama barang).
- `pengganti = 1`: aturan "pengganti terakhir" (keperluan lalu nama), hanya bila tak ada aturan biasa/memori yang cocok; skor 40. Urutan akhir:
  keperluan → mirip keperluan → memori → nama → pengganti → kemiripan → tanpa rekomendasi.
- Admin dapat menambah aturan dengan kualifier mal dan penanda pengganti di **Pengaturan penautan**; lencana mal/pengganti tampil di daftar.
- Seed CCD (urutan = prioritas): Sticker Lift; Sticker Main Gate (`MAIN GATE|KACA LOBBY|GATE LOBBY`, ukuran stiker 117×40/185, keperluan "gate lobby utama");
  T-Banner per mal; Hanging Banner (`HANGING|GIANT BANNER`); Neon Box Beach Gate / Foodcourt (regex AND; albatros foodcourt / A1 59,4×84,1); Cover FOH/Backstage;
  KOL/Talent (`KOL|TALENT|ENDORSE`); Giveaway; Press Conference; Kuota; IG Ads; Subscription Adobe/Canva/Capcut/Envato/Freepik/GoogleOne;
  pengganti: bahan cetak apa pun (`STICKER|BANNER|POSTER|ALBATROS|VINYL|WALL SIGN|…`) dan keperluan "administrasi departemen creative"/"media cetak" → *Materi cetak lainnya*.
  **Pos nonaktif dilewati** (saat ini Cover FOH, Cover Backstage, Subscription Adobe): cover jatuh ke *Materi cetak lainnya*; aktifkan posnya bila ingin direkomendasikan.
- Uji: `php spark mic:fs-uji-rekomendasi` (kasus CCD + memori-tak-menimpa-mal); `--laporan` bila `writable/flowstore-fixture-dgm.json` ada
  (`php scripts/fs-buat-fixture.php --divisi=35 --keluar=writable/flowstore-fixture-dgm.json`; status BTB fixture jadikan `draft`) mencetak cakupan rekomendasi
  2026, yang meragukan, dan perbandingan BTB per pos per bulan dengan Budget Departemen (hanya baca).

**Tautan BTB → item Creative** (`budget_fs_pemetaan.creative_scope` `standalone|event`, `creative_item_id`; 0 = dipilih "tidak terkait Creative"):
- Hanya dept CCD (nama dept "Creative Concept & Design"): kolom **Untuk Creative** di tab FlowStore = pemilih bertingkat Tema → item. Tema = Tema Bulanan / event pada
  bulan terima atau bulan beban ± 1 (daftar mengikuti pilihan bulan beban) + "Tanpa Tema" (item lama). Item "Rencana" dan Tema yang ditutup tidak bisa dipilih.
- **Usulan otomatis** (`CreativeBiayaFs::saran`): jenis media (Sticker Lift, T-Banner, Hanging, Neon Box, Poster…) +50, ukuran sama +40, kata nama item +10; Tema: bulan
  disebut keperluan ("WHATS ON OKTOBER" → Tema Bulanan Oktober) +30, nama event disebut +40, Tema pada bulan terima +10. Minimal 60 dan pemenang tunggal (seri = tanpa usulan);
  alasan tampil di lencana "usulan".
- Draf tersimpan otomatis bersama pilihan pos/bulan. **Catat**: realisasi Budget Dept dibuat seperti biasa (pos + bulan) dan tautan tersimpan; **tidak** ada baris `creative_realisasi` baru.
  Menghapus realisasi hasil catat melepas tautan (item kembali ke antrean tanpa tautan). Baris yang sudah dicatat menampilkan "Tema › Item" (tautan ke /creative).
- Modul Creative hanya MEMBACA (`App\Libraries\CreativeBiayaFs`): realisasi item = `creative_realisasi` (manual lama) + realisasi Budget Dept dari BTB tertaut (status dicatat/perlu ditinjau);
  kartu Tema, KPI, Summary bulanan (+ cetak) memakai gabungan (bulan = bulan beban); tanpa tautan angkanya identik dengan sebelumnya. Rincian BTB (tanggal, BTB/PO, supplier, barang, nilai,
  lencana FlowStore) ada di pane Realisasi item; penanda "kemungkinan dobel" bila ada realisasi manual di bulan yang sama dengan BTB tertaut (hanya penanda).
- Hak: tab & Catat mengikuti hak `budget_dept` dept CCD (dept sendiri/admin menulis; Deputy/GM hanya lihat). Tim Creative tanpa hak `budget_dept` hanya melihat biaya tertaut
  (hak `creative_main`); tautan "Buka antrean FlowStore" hanya tampil bila pengguna punya `budget_dept` dan CCD terlihat (admin / dept sendiri). **Untuk deploy**: beri dept CCD hak
  `budget_dept` (lokal sudah: dept 3 lihat+ubah) dan pastikan tanggal mulai penarikan CCD (bawaan 1 Okt 2026; data Jul–Agu di `creative_realisasi` dibiarkan, tak bertabrakan).
- Batasan: tab Creative event dan ringkasan event belum membaca biaya FlowStore; biaya item event hanya tampil di /creative. Tautan tidak memindahkan biaya antar-bulan di Creative
  (mengikuti bulan beban Budget Dept).

## Konfigurasi `.env`

```
flowstore.mode    = http            # http | fixture (kosong: http bila url+token terisi, selain itu "mati")
flowstore.url     = https://<host FlowStore>
flowstore.token   = <X-Feed-Token>  # JANGAN dicetak/di-commit
flowstore.timeout = 20              # detik (opsional)
flowstore.mulai   = 2026-10-01      # tanggal mulai penarikan (opsional; bawaan 2026-10-01; admin bisa menimpa di tab)
flowstore.fixture = <path>          # opsional, bawaan writable/flowstore-fixture.json
```

Driver ada di satu kelas, `app/Libraries/FlowstoreFeed.php`: timeout, pesan galat ramah (tanpa token/URL), kursor
`setelah_id` sampai habis, dan mulai-ulang sekali bila FlowStore menjawab 400 `FEED_KURSOR_TIDAK_DIKENAL`.
Mode `fixture` membaca berkas JSON berbentuk sama (tab menampilkan label "DATA CONTOH").
Asumsi: `dari`/`sampai` pada feed menyaring `tanggal_diterima` (= `COALESCE(tanggal_diterima, tanggal_btb)`).

## Perintah

```
php spark mic:fs-sync [--dari YYYY-MM-DD] [--sampai YYYY-MM-DD] [--dept ID]   # --dari dipangkas ke tanggal mulai
php spark mic:fs-uji-rekomendasi
php scripts/fs-buat-fixture.php [--divisi=40,46,22]       # fixture dari salinan lokal web_store_db (data nyata: jangan di-commit)
FLOWSTORE_TIRUAN_TOKEN=x FLOWSTORE_TIRUAN_BERKAS=fixture.json php -S 127.0.0.1:8099 scripts/flowstore-tiruan.php   # server tiruan
                                                                  # + FLOWSTORE_TIRUAN_FEED_LAMA=1 membuang field keterangan order (uji toleransi)
php spark migrate                                          # pakai PHP XAMPP (/Applications/XAMPP/xamppfiles/bin/php); php Homebrew tak bisa konek DB lokal
```

Bawaan `mic:fs-sync`: dari **tanggal mulai penarikan** (bawaan 1 Okt 2026) sampai hari ini, semua dept yang punya pemetaan divisi.
**Cron yang disarankan** (harian, pukul 06:15):

```
15 6 * * * /opt/alt/php81/usr/bin/php /home/<akun>/<mic>/spark mic:fs-sync >> /home/<akun>/<mic>/writable/logs/fs-sync.log 2>&1
```

Server tiruan memakai aturan token feed asli: token server kosong → 404, token salah/tidak ada → 401.

## Tabel (migrasi `2026-10-17-000002_PenautanFlowstore`)

`fs_divisi`, `fs_btb_item` (cermin), `budget_fs_divisi_map` (satu divisi hanya milik satu dept; seed 12 dept yang namanya
cocok, sisanya diatur admin), `budget_fs_pemetaan` (UNIK per `id_btb_item`), `budget_fs_aturan` (memori + kata kunci;
seed IT), `budget_fs_sinkron` (catatan tiap sinkron untuk "terakhir sinkron"/galat). Tabel lama tidak diubah.
Migrasi `2026-10-18-000010_CcdFlowstore` menambah `budget_fs_aturan.mal/pengganti` dan `budget_fs_pemetaan.creative_scope/creative_item_id` (+ seed aturan CCD).
Migrasi `2026-10-17-000003_FsKeperluan` menambah kolom `fs_btb_item.keperluan/spesifikasi/no_mr/keterangan_pr/po/btb` dan
`budget_fs_aturan.sasaran` (+ seed aturan keperluan IT); `2026-10-17-000004_FsMulaiTarik` menambah tabel `budget_fs_setelan`
(tanggal mulai penarikan: `dept_id` 0 = semua dept).

## Hak akses

Lihat: siapa pun yang boleh melihat dept (seperti Realisasi) dan dept-nya punya pemetaan divisi (admin: selalu, agar bisa
menyetelnya). Tulis (sinkron, catat, abaikan): admin atau dept sendiri. Pengaturan divisi, kata kunci & tanggal mulai penarikan: admin. Semua POST
butuh CSRF; pos divalidasi milik dept; output di-escape.

## Batasan

- Cermin menyimpan baris yang sudah ditarik; item yang tak pernah muncul di rentang sinkron tak terdeteksi hilang.
- Divisi FlowStore yang dipetakan ke dua dept tak didukung (satu item hanya boleh dicatat sekali).
- Mengubah realisasi hasil catat lewat tombol Ubah di tab Realisasi tidak memicu "perlu ditinjau" (hanya perubahan di FlowStore).
- Debug Toolbar CodeIgniter (CI_ENVIRONMENT=development) menampilkan isi `.env` di halaman; di produksi nonaktif.
