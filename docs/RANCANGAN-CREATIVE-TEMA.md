# Rancangan Creative: Tema, Design/Motion Graphic + Turunan, Media Cetak

Status: DISETUJUI pemilik 18 Okt 2026. Tahap 1, Tahap 2a dan Tahap 2b selesai di LOKAL (belum commit/deploy); Tahap 3 dan seterusnya belum dibangun.
Dokumen ini adalah acuan agen/pengembang; ubah di sini bila keputusan berubah.

## 1. Prinsip
- Induk pekerjaan Creative adalah **Tema**. Item (Design, Motion Graphic, Konten Digital,
  Media Cetak, Influencer, Media Prescon) adalah pelengkap Tema.
- Satu sumber kebenaran per hal: item event dikerjakan di Events, titik media di Media Promo.
- Data tidak dihapus diam-diam; perubahan otomatis selalu bisa dilihat dan dibatalkan.

## 2. Tema (Tahap 1, sudah ada di lokal)
- Tema = **Event** (modul Events) atau **Tema Bulanan** (tabel `creative_tema`).
- Daftar `/creative` per bulan (terbaru di atas); kartu Tema berlencana Event/Bulanan, dapat dibuka.
- Item event dikerjakan di tab Creative event; di `/creative` baca-saja + tombol "Isi di event".
- Tema ditutup **manual** oleh lead (pemegang hak Setujui) atau admin -> tab Terkunci; dapat dibuka kembali.
- Pemetaan 140 item lama lewat panel usulan; tim yang mengonfirmasi.

## 3. Paket item awal
- Saat Tema dibuat, sistem membuat item awal berstatus "Rencana" (tanpa budget/tanggal), diberi nama
  jenisnya saja ("Design", "Poster", ...): nama Tema TIDAK ditulis di nama item karena item sudah berada di
  dalam kartu Tema (diputuskan 18 Okt). Tim menghapus yang tidak perlu.
- Isi awal (dapat diatur lead/admin): Design 1, Motion Graphic 1, Konten Digital (Feed Instagram,
  Story/Reels), Media Cetak (Poster, X-Banner; paket event boleh lebih lengkap, mis. Backdrop).
  Influencer dan Media Prescon tidak otomatis.
- Event: paket dibuat saat event dibuat (di tab Creative event). Tema/event lama TIDAK otomatis;
  tersedia tombol "Tambahkan paket awal".
- Saat Tema ditutup: tanyakan "N item belum dikerjakan: hapus atau tetap?".

## 4. Design dan Motion Graphic (induk) + Turunan
- Design (dulu "Master Design") dan Motion Graphic adalah induk. Keduanya memakai alur
  Opsi & Persetujuan yang ada.
- **Turunan**: banyak per induk, multi-ukuran/format. Berasal dari **Katalog Turunan** (dapat diatur
  lead/admin): Design = Digital (Feed IG 1080x1350 & 1080x1080, Story/Reels 1080x1920, Carousel
  1080x1350, Poster WhatsApp 1080x1080, Layar digital/TV 1920x1080 & 1080x1920, LED Videotron ukuran
  per layar, Banner web 1920x600) dan Cetak (Poster A3/A2, Flyer A5, X-Banner 60x160, Roll-up 85x200,
  Standing Sign A1, Tent Card A5 lipat, Backdrop/Spanduk/Baliho/Stiker bebas, ID Card 54x86).
  Motion Graphic = format video (9:16, 4:5, 1:1, 16:9, LED/TV loop) x durasi (6/15/30/60 dtk).
- Di kartu induk ada **daftar turunan yang bisa dicentang** (apa saja yang harus dibuat; ukuran terisi
  dari katalog). Tiap turunan: status (Belum, Dikerjakan, Review, Selesai), PIC, tenggat, berkas.
- **Persetujuan per turunan oleh lead** (pemegang hak Setujui) -- berlaku juga untuk turunan otomatis.
- Kartu induk menampilkan ringkasan "N dari M turunan selesai".

## 5. Media Cetak (berdiri sendiri)
- Item sendiri dengan **jenis** (T-Banner, Hanging, Sticker Lift, Totem, Neon Box, Materi Cetak
  Lainnya, dst.) dan **budget wajib (> 0)**.
- **Titik** dipilih admin dari master Media Promo (`promo_media_spots`: t_banner 37, hanging 5,
  sticker_lift 7, totem, digital 17). Memilih titik **membuat pemesanan terhubung di Media Promo**
  (cek bentrok slot; persetujuan otomatis/disederhanakan karena pemesan internal Creative).
  Titik **opsional** untuk jenis tanpa master (Neon Box, Materi Cetak Lainnya).
- **Turunan design otomatis**: 1 kebutuhan per (jenis, ukuran) di Design Tema -- BUKAN per titik dan
  bukan per item. Ukuran berbeda -> kebutuhan berbeda. Keterangan kecil "dipakai di N titik, M item cetak".
  Bila Tema belum punya Design, Design induk dibuat otomatis. Hapus/ubah media cetak: kebutuhan ikut
  hilang/berubah bila belum dikerjakan; bila sudah dikerjakan tetap ada dan diberi penanda.
- **Realisasi**: bukti pasang (foto) **per titik**; harga dicatat **per item** (total cetak).
- 12 item cetak lama (budget Rp 18,6 jt) diisi jenis & ukurannya saat pemetaan (usulan dari nama).

## 6. Konten Digital
- Item sendiri (jadwal tayang, platform, PIC, hasil tayang/insight). Boleh memilih titik layar digital
  (master Media Promo, 17 titik). Format/ukuran memunculkan kebutuhan turunan Design otomatis dengan aturan
  yang sama seperti Media Cetak (DIPUTUSKAN 18 Okt 2026: ya, satu kebutuhan per format/ukuran).
- Status disederhanakan: Rencana, Produksi, Tayang, Dilaporkan; PIC wajib; tenggat dan target reach tersimpan
  (perbaikan bug 17 Okt).

## 7. Tahapan
1. (selesai lokal) Tahap 1: Tema, daftar per bulan, Terkunci, label Design, Motion Graphic, pemetaan.
2. (selesai lokal 18 Okt) Tahap 2a: katalog turunan, Design/Motion Graphic + turunan + persetujuan lead, paket item awal,
   jenis media + ukuran pada Media Cetak (kolom `jenis_media`, `ukuran`), tampilan padat. Rincian: `docs/CREATIVE-TURUNAN.md`.
3. (selesai lokal 18 Okt) Tahap 2b: Media Cetak (jenis, ukuran, periode pasang, titik Media Promo + pemesanan terhubung, turunan otomatis, realisasi + bukti pasang). Rincian: bagian 10 dan `docs/CREATIVE-TURUNAN.md`.
4. Tahap 3: Konten Digital (status, PIC wajib, titik layar, turunan otomatis).
5. Tahap 4: pandangan performa tim (volume, hasil tayang, biaya, siklus persetujuan, ketepatan waktu,
   disertai kelengkapan pencatatan).

## 8. Keputusan tambahan (18 Okt 2026) dan catatan
- Konten Digital: aturan turunan otomatis SAMA dengan Media Cetak (satu kebutuhan per format/ukuran).
- Pemesanan titik dari Creative: DISETUJUI OTOMATIS (pemesan internal; sumber 'internal', atas nama pembuat);
  hanya bentrok slot yang menolak. Tidak ada klik persetujuan tambahan oleh lead.
- Ukuran tiap layar LED: diisi tim di katalog/master titik.
- Pencatatan Creative menurun sejak Agustus (Mei 37, Jun 49, Jul 36, Agu 10, Sep 1 item): perlu dipahami
  sebelum performa dihitung.

## 9. Tahap 2a (dibangun di lokal 18 Okt 2026)
- Katalog Turunan (`creative_turunan_jenis`, 22 jenis seed) dan Paket Item Awal (`creative_paket_awal`) diatur lead/admin di
  `/creative/katalog`. Turunan (`creative_turunan`, `creative_turunan_file`) pada Design/Motion Graphic dengan persetujuan per turunan oleh lead.
- Item "Rencana" (`dari_paket = 1`) tidak ikut KPI/tab/summary; dihitung terpisah ("N item rencana"). Lencana hilang bila item diubah/diisi.
  Menutup Tema yang masih punya item Rencana menampilkan dialog hapus / tetap / batal.
- Media Cetak: `jenis_media` + `ukuran` (nullable). Item lama tidak diisi otomatis; panel pemetaan menyajikan USULAN ukuran (regex angka x angka)
  yang harus dikonfirmasi tim. Budget wajib, titik Media Promo, turunan otomatis tetap Tahap 2b.
- Tampilan padat: item = baris tabel (≤ 44 px, terukur 36 px), judul kolom sekali per grup, aksi ikon, Tema ber-item rencana saja ringkas.

## 10. Tahap 2b (dibangun di lokal 18 Okt 2026)
Keputusan pemilik (A-H) dijalankan apa adanya; yang perlu diketahui pengembang:
- **Media Cetak berdiri sendiri**: budget WAJIB > 0 untuk item cetak yang disimpan lewat formulir (baru atau diubah, termasuk item "Rencana" yang diisi);
  item lama berbudget tetap, item Rencana dari paket boleh 0 sampai diisi. Kolom baru `pasang_mulai`, `pasang_selesai`, `design_ref` (item cetak standalone dan event).
  Jenis media punya master titik: T-Banner, Hanging Banner, Sticker Lift, Totem (peta di `CreativeTitikService::JENIS_TIPE`); Neon Box, Materi Cetak Lainnya dan jenis
  lain tidak punya titik. Enam jenis ditambahkan ke katalog (`creative_turunan_jenis`, ukuran bebas) oleh migrasi `2026-10-18-000004`.
- **Memilih titik = membuat pemesanan di Media Promo** (`promo_media_usage`): sumber internal, dept Creative, atas nama pengguna, `status = approved` langsung,
  `creative_ref = 'standalone:ID' | 'event:ID'`, `batch_id` satu per item. Tautan di tabel `creative_cetak_titik` (juga menyimpan bukti pasang per titik).
  Bentrok dihitung oleh `PromoMediaUsageModel::pemakaiCetak` (aturan yang sama dengan `hasConflictCetak`, diekstrak ke satu kueri bersama), diproses dalam transaksi
  dengan `SELECT ... FOR UPDATE` pada baris titik sehingga dua orang tak bisa memesan titik yang sama. Satu titik bentrok menolak seluruh pilihan dan menyebut pemakainya.
  Mencabut titik / menghapus item menghapus pemesanan (yang sudah `done` tidak dapat dicabut; saat item dihapus tetap sebagai riwayat). Mengubah periode pasang
  memperbarui semua pemesanan atau ditolak bila bentrok. Tema ditutup (atau modul event diselesaikan) mengunci titik, pemesanan dan bukti.
- **Turunan Design otomatis** (`CreativeTurunanOtomatis`, generik untuk Tahap 3): satu kebutuhan per (Design, jenis, ukuran), keterangan "Dipakai di N titik, M item cetak",
  Design dibuat otomatis bila Tema belum punya, Design pertama bawaan atau pilihan `design_ref` bila Tema punya lebih dari satu. Item "Rencana" tidak menimbulkan kebutuhan.
  Kebutuhan yang tak dipakai lagi dihapus bila belum dikerjakan, selain itu diberi penanda "Tidak lagi dipakai media cetak (alasan)". Kebutuhan otomatis tidak bisa dihapus
  manual selama masih dipakai. Daftar turunan menandai "dari Media Cetak" vs "manual".
- **Realisasi**: pane Realisasi item cetak punya dua bagian, "Biaya (per item)" (tabel realisasi yang ada) dan "Bukti pasang (per titik)" (foto + tanggal + catatan per titik,
  indikator "N dari M titik terpasang"). Kolom "Bukti terpasang" lama pada tabel biaya hanya tampil bila ada data lama. Pane baru "Titik" memuat chip titik + tombol Pilih titik.
- Item cetak event memakai komponen yang sama (partial `_creative/_cetak_titik`, `_cetak_bukti`, `_titik_picker`); di `/creative` tampil baca-saja dengan "Isi di event".
- Halaman Media Promo hanya mendapat lencana "dari Creative" (halaman utama, daftar milik saya, tooltip gantt); pemesanan Creative berstatus approved sehingga tidak masuk antrean Pending.
- Log: modul `creative_cetak` (pilih/cabut titik, periode, bukti); turunan otomatis tercatat di `creative_turunan`.

## 11. Biaya CCD: FlowStore sebagai sumber (keputusan pemilik 18 Okt 2026, dibangun di lokal)
- Pengeluaran CCD lewat FlowStore (divisi DGM). **FlowStore = SUMBER realisasi biaya CCD**: BTB dipetakan ke pos CCD di tab FlowStore Budget Departemen (usulan → Catat); realisasi biaya
  **tidak diinput ulang** di modul Creative. Creative menyimpan rincian (titik, bukti pasang) dan MENAMPILKAN biaya dari BTB yang ditautkan ke item.
- Tautan opsional per baris BTB: pemilih Tema → item (dengan usulan otomatis berdasar jenis media/ukuran/bulan/nama event). Item "Rencana" belum bisa ditautkan (isi dulu).
- Realisasi item = `creative_realisasi` (data lama/manual, tidak diubah) + BTB tertaut; serapan, total Tema, KPI, Summary bulanan (+ cetak) memakai gabungan. Data Jul–Agu tetap; penarikan FlowStore mulai Okt 2026.
- Tidak dobel: modal Realisasi memberi catatan "Biaya baru diambil dari FlowStore; isi manual hanya untuk biaya di luar FlowStore"; item dengan realisasi manual di bulan yang sama dengan BTB tertaut diberi penanda "Kemungkinan dobel" (angka tak diubah).
- Hak: tautan & Catat mengikuti `budget_dept` dept CCD; tim Creative tanpa hak itu hanya melihat. Rincian teknis: `docs/PENAUTAN-FLOWSTORE.md` (bagian CCD).
