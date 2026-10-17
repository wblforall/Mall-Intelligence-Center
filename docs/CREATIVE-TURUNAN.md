# Creative Tahap 2a: Turunan, Katalog, Paket Item Awal

## Tabel (migrasi 2026-10-18-000001..000003)
| Tabel | Isi |
|---|---|
| `creative_turunan_jenis` | Katalog jenis turunan: induk (design / motion_graphic), kelompok (digital / cetak / video), nama, `ukuran_baku` JSON (daftar opsi atau `{"bebas":true}`), `durasi_opsi` JSON (motion), aktif, urutan. Jenis yang sudah dipakai tidak dihapus, hanya dinonaktifkan. |
| `creative_turunan` | Satu kebutuhan turunan per (scope `standalone`/`event`, item_id, jenis, ukuran, durasi). `kunci` UNIQUE mencegah dobel. Status kerja (belum, dikerjakan, review, selesai), PIC, tenggat, catatan, `approval_status` (belum, diajukan, disetujui, ditolak), `versi`, pelaku/waktu, `alasan`, `sumber` (manual / otomatis, otomatis dipakai Tahap 2b). |
| `creative_turunan_file` | Berkas hasil per turunan (`uploads/creative-turunan/{id}/`, validasi unggahan yang sama dengan Creative: gambar + dokumen, maks 20 MB). |
| `creative_paket_awal` | Baris paket item awal per konteks (bulanan / event): jenis item, nama (boleh memakai `{tema}`, tidak dianjurkan), platform, jenis media + ukuran bawaan (cetak), urutan, aktif. |
| `creative_items.dari_paket`, `event_creative_items.dari_paket` | 1 = item "Rencana" dari paket yang belum dikerjakan. |
| `creative_items/event_creative_items.jenis_media, ukuran` | Jenis media + ukuran Media Cetak (NULL bila belum diisi). |

## Alur turunan
`belum` -> Ajukan (hak ubah; wajib ada berkas) -> `diajukan` -> lead Setujui (`disetujui`, status Selesai, terkunci) atau Tolak (alasan min. 10 karakter -> `ditolak`, versi naik, bisa diajukan lagi).
Turunan disetujui tidak bisa diubah / dihapus / diberi berkas sampai lead Membuka kembali (versi naik). Tema ditutup (atau modul event diselesaikan) mengunci semua turunannya.
Induk yang punya turunan disetujui tidak bisa dihapus; menghapus induk menghapus turunan + berkasnya. Ringkasan di kartu induk: "N dari M turunan selesai" (selesai = disetujui).
Semua aksi tercatat di Activity Log (modul `creative_turunan`, `creative_paket`).

## Hak
- Lihat: siapa pun yang boleh membuka halamannya.
- Ubah / ajukan / unggah / hapus: hak ubah `creative_main` (standalone) atau `creative` (event).
- Setujui / tolak / buka kembali, katalog, paket awal, tutup Tema: lead = `canApproveCreative()` (admin, manager, atau hak Setujui).
- Item event: turunan dikelola di tab Creative event; di `/creative` tampil baca-saja dengan tombol "Isi di event".

## Paket item awal
Dibuat otomatis saat Tema Bulanan dibuat (`CreativeTemaCtrl::tambah`) dan saat Event dibuat (`Events::store`). Tema/event lama: tombol "Tambahkan paket awal"
(kartu Tema bulanan, dan tab Creative event); idempoten (jenis + nama yang sama tidak dibuat dua kali). Influencer dan Media Prescon tidak otomatis.

## Rute
`POST creative/turunan/{standalone|event}/{id}/{buat|ubah|hapus|unggah|hapus-berkas|ajukan|setujui|tolak|buka}`, `GET creative/katalog` (+ `POST creative/katalog/jenis|paket/...`),
`POST creative/tema/paket-awal`, `POST events/{id}/creative/paket-awal`, `POST creative/pemetaan/ukuran`.

## Tahap 2b: Media Cetak, titik Media Promo, turunan otomatis (migrasi 2026-10-18-000004)
| Tabel / kolom | Isi |
|---|---|
| `creative_items`, `event_creative_items` : `pasang_mulai`, `pasang_selesai` (DATE NULL), `design_ref` (VARCHAR 40 NULL) | Periode pasang Media Cetak dan Design induk pilihan ('standalone:ID' / 'event:ID'; NULL = Design pertama Tema). |
| `creative_cetak_titik` | Tautan item cetak -> pemesanan -> titik: `scope`, `item_id`, `usage_id`, `spot_id`, `slot_number`, `tanggal_pasang`, `bukti_pasang_file` + `bukti_pasang_asli` (`uploads/creative-cetak-titik/{id}/`), `catatan`. UNIQUE (scope, item_id, spot_id). |
| `promo_media_usage.creative_ref` | 'standalone:ID' / 'event:ID' bila pemesanan dibuat Creative (NULL = bukan). |
| `creative_turunan.keterangan`, `penanda` | "Dipakai di N titik, M item cetak" dan penanda "Tidak lagi dipakai media cetak (...)" untuk turunan otomatis. |
| `creative_turunan_jenis` | Enam jenis cetak baru (T-Banner, Hanging Banner, Sticker Lift, Totem, Neon Box, Materi Cetak Lainnya), ukuran bebas; hanya ditambah bila namanya belum ada. |

### Aturan Media Cetak
- Budget wajib > 0 pada formulir item cetak (baru / diubah / Rencana yang diisi); item lama berbudget tidak diubah.
- Titik hanya untuk jenis yang punya master (T-Banner, Hanging Banner, Sticker Lift, Totem). Periode pasang harus diisi lengkap; item Rencana belum boleh memilih titik.
- Panel "Pilih titik": titik tipe sesuai dikelompokkan per area, ada pencarian, "pilih semua di area", "hanya yang tersedia", titik bentrok dinonaktifkan dan menyebut pemakainya;
  tanda segitiga bila ukuran titik berbeda dengan ukuran item (hanya peringatan).
- Memilih titik membuat pemesanan `approved` (dept Creative, sumber internal) tanpa langkah persetujuan lain. Gagal seluruhnya bila ada titik bentrok.
- Mencabut titik menghapus pemesanan; titik yang sudah ditandai terpasang butuh konfirmasi (bukti ikut terhapus); pemesanan `done` tidak dapat dicabut.
- Mengubah periode memperbarui pemesanan (ditolak bila bentrok atau ada pemesanan done); mengganti jenis ditolak selama ada titik yang tipenya tidak cocok.
- Menghapus item melepas semua pemesanan (yang `done` tetap riwayat) dan foto bukti; menghapus event melepas titik item event-nya.

### Turunan otomatis (`CreativeTurunanOtomatis::sinkron($temaKey, $sumber, $alasan)`)
- `$temaKey` 't5' (Tema Bulanan) / 'e13' (Event); sumber 'cetak' sekarang, 'digital' disiapkan untuk Tahap 3 (tambah cabang di `kumpulkan()`).
- Satu kebutuhan per (Design, jenis, ukuran dinormalkan: "1×3 m" = "1 X 3 M"); ukuran lain = kebutuhan lain. Baris: `sumber = otomatis`, `sumber_ref = cetak:t5`, `kunci` unik.
- Design dibuat otomatis (nama "Design") bila Tema belum punya; item "Rencana" (`dari_paket = 1`) tidak menimbulkan kebutuhan.
- Hapus / ubah item: kebutuhan yang tak dipakai lagi dihapus bila `status = belum`, belum diajukan dan tanpa berkas; selain itu tetap ada dengan `penanda`.
- Dipanggil setelah item cetak ditambah, diubah, dihapus, dipindah Tema, dan setelah titik dipilih / dicabut. Hak setuju turunan otomatis sama dengan turunan manual (lead).

### Rute dan hak
`GET creative/titik/{standalone|event}/{itemId}/daftar`, `POST creative/titik/{scope}/{itemId}/pilih`, `POST creative/titik/{scope}/{titikId}/{cabut|bukti|bukti-hapus}`.
Lihat daftar = hak lihat menu; pilih / cabut / bukti = hak ubah menu (`creative_main` standalone, `creative` event). Log: modul `creative_cetak`.

