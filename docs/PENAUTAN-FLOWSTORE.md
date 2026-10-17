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
