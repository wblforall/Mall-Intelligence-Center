<?php
/**
 * Pembuat fixture feed FlowStore untuk uji lokal — membaca salinan lokal web_store_db
 * dan menulis berkas JSON berbentuk SAMA dengan GET /api/feed/btb-item + /api/feed/divisi.
 *
 *   php scripts/fs-buat-fixture.php [--divisi=40,46,22] [--keluar=writable/flowstore-fixture.json]
 *
 * Aturan yang dipakai (sederhana, hanya untuk uji):
 *  - nilai_diterima = po_item.totalPerItem * jumlah_diterima / jumlahAsli bila jumlahAsli > 0
 *                     (sumber_nilai 'po_proporsional'), selain itu btb_item.biaya ('btb_biaya').
 *  - ppn_rupiah ikut diproporsikan; nilai_sebelum_ppn = nilai_diterima - ppn_rupiah.
 *  - Salinan lokal belum punya BTB final dan tanggal_diterima: tanggal_diterima = tanggal_btb,
 *    status 'draft' (seperti FlowStore asli: semua BTB draft = sudah diterima), disisipi
 *    sebagian 'final' dan 'batal' (crc32 id) agar jalur itu teruji. HASILNYA BUKAN DATA PRODUKSI.
 *  - Keterangan order (meniru feed baru): keterangan_pr = pr_item.keterangan, keterangan_po = po_item.keterangan,
 *    keterangan_btb = btb_item.keterangan; keperluan = gabungan siap tampil (maks 500) dengan prioritas PR → MR
 *    (mr_item.keterangan) → PO → BTB; spesifikasi = pr_item.spesifikasi, bila kosong mr_item.spesifikasi (maks 500);
 *    no_mr = pr_item.noMR, bila kosong pr.noMR, bila kosong mr.no_mr.
 * Berkas keluaran memuat data nyata (nama supplier/harga): jangan di-commit (writable/flowstore-*.json diabaikan git).
 */
$opt = getopt('', ['divisi::', 'keluar::']);
$divisi = array_filter(array_map('intval', explode(',', $opt['divisi'] ?? '40,46,22')));
$keluar = $opt['keluar'] ?? __DIR__ . '/../writable/flowstore-fixture.json';

$pdo = new PDO('mysql:host=localhost;dbname=web_store_db;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$in  = implode(',', $divisi);
$sql = "SELECT bi.id_btb_item, b.id_btb, b.no_btb, b.tanggal_btb, b.tanggal_diterima, b.created_at AS dibuat, b.biaya AS biaya_btb_item,
               bi.biaya AS biaya_item, bi.nama_barang, bi.kode_barang, bi.jumlah_diterima, bi.id_barang,
               po.id_PO, po.noPO, po.tanggalPO, b.id_supplier, COALESCE(NULLIF(sp.namaSupplier,''), b.nama_supplier) AS nama_supplier, COALESCE(po.id_skema, b.id_skema) AS id_skema, sk.skema,
               pr.id_PR, pr.noPR, pr.id_divisi, dv.divisi, dv.kode AS kode_divisi, pri.kodeBarang,
               pi.totalPerItem, pi.jumlahAsli, pi.hargaSatuan, pi.ppnRupiah, st.satuan, kb.nama_kategori,
               pri.keterangan AS ket_pr, pri.spesifikasi AS spes_pr, pri.noMR AS mr_pri, pr.noMR AS mr_pr, mrr.no_mr AS mr_mr,
               mi.keterangan AS ket_mr, mi.spesifikasi AS spes_mr, pi.keterangan AS ket_po, bi.keterangan AS ket_btb
          FROM btb_item bi
          JOIN btb b ON b.id_btb = bi.id_btb
          JOIN po_item pi ON pi.id_POItem = bi.id_POItem
          JOIN po ON po.id_PO = pi.id_PO
          JOIN pr_item pri ON pri.id_PRItem = pi.id_PRItem
          JOIN pr ON pr.id_PR = pri.id_PR
          LEFT JOIN mr_item mi ON mi.id_mr_item = pri.id_mr_item
          LEFT JOIN mr mrr ON mrr.id_mr = pr.id_mr
          LEFT JOIN supplier sp ON sp.id_supplier = COALESCE(po.id_supplier, b.id_supplier)
          LEFT JOIN divisi dv ON dv.id_divisi = pr.id_divisi
          LEFT JOIN skema sk ON sk.id_skema = COALESCE(po.id_skema, b.id_skema)
          LEFT JOIN satuan st ON st.id_satuan = bi.id_satuan
          LEFT JOIN barang br ON br.id_barang = bi.id_barang
          LEFT JOIN kategori_barang kb ON kb.id_kategori = br.id_kategori
         WHERE pr.id_divisi IN ($in)
         ORDER BY bi.id_btb_item";
$bersih = function ($v, int $maks = 500) {
    $v = trim((string) $v);
    return $v === '' ? null : mb_substr($v, 0, $maks);
};
$items = [];
foreach ($pdo->query($sql, PDO::FETCH_ASSOC) as $r) {
    $qty = (float) $r['jumlah_diterima'];
    $asli = (float) $r['jumlahAsli'];
    if ($asli > 0 && $r['totalPerItem'] !== null) {
        $nilai = round((float) $r['totalPerItem'] * $qty / $asli, 2);
        $ppn   = round((float) $r['ppnRupiah'] * $qty / $asli, 2);
        $sumber = 'po_prorata';
    } else {
        $nilai = round((float) $r['biaya_item'], 2);
        $ppn   = null;
        $sumber = 'btb_biaya';
    }
    $h = crc32((string) $r['id_btb_item']);
    // Meniru FlowStore asli: semua BTB 'draft' (artinya sudah diterima). Sedikit 'final' dan 'batal' disisipkan agar jalur itu teruji.
    $status = $h % 37 === 0 ? 'batal' : ($h % 11 === 0 ? 'final' : 'draft');
    $items[] = [
        'id_btb_item' => (int) $r['id_btb_item'], 'id_btb' => (int) $r['id_btb'], 'no_btb' => $r['no_btb'],
        'tanggal_btb' => $r['tanggal_btb'], 'tanggal_diterima' => $r['tanggal_diterima'] ?: $r['tanggal_btb'], 'status_btb' => $status,
        'id_po' => (int) $r['id_PO'], 'no_po' => $r['noPO'], 'tanggal_po' => $r['tanggalPO'],
        'id_supplier' => $r['id_supplier'] !== null ? (int) $r['id_supplier'] : null, 'nama_supplier' => $r['nama_supplier'],
        'id_skema' => $r['id_skema'] !== null ? (int) $r['id_skema'] : null, 'skema' => $r['skema'],
        'id_pr' => (int) $r['id_PR'], 'no_pr' => $r['noPR'],
        'id_divisi' => (int) $r['id_divisi'], 'divisi' => $r['divisi'], 'kode_divisi' => $r['kode_divisi'],
        'kode_barang' => $r['kode_barang'] ?: ($r['kodeBarang'] ?: null), 'nama_barang' => $r['nama_barang'],
        'kategori' => $r['nama_kategori'], 'satuan' => $r['satuan'],
        'jumlah_diterima' => $qty, 'harga_satuan_po' => $r['hargaSatuan'] !== null ? (float) $r['hargaSatuan'] : null,
        'nilai_diterima' => $nilai, 'nilai_sebelum_ppn' => $ppn !== null ? round($nilai - $ppn, 2) : $nilai, 'ppn_rupiah' => $ppn,
        'sumber_nilai' => $sumber, 'dibuat_pada' => $r['dibuat'],
        'keterangan_pr' => $bersih($r['ket_pr'], 2000), 'keterangan_po' => $bersih($r['ket_po'], 2000), 'keterangan_btb' => $bersih($r['ket_btb'], 2000),
        'keperluan' => $bersih($r['ket_pr'] ?: ($r['ket_mr'] ?: ($r['ket_po'] ?: $r['ket_btb']))),
        'spesifikasi' => $bersih($r['spes_pr'] ?: $r['spes_mr']),
        'no_mr' => $bersih($r['mr_pri'] ?: ($r['mr_pr'] ?: $r['mr_mr']), 100),
    ];
}
$div = [];
foreach ($pdo->query('SELECT id_divisi, divisi, kode FROM divisi ORDER BY id_divisi', PDO::FETCH_ASSOC) as $d) {
    $div[] = ['id_divisi' => (int) $d['id_divisi'], 'divisi' => $d['divisi'], 'kode' => $d['kode']];
}
file_put_contents($keluar, json_encode(['versi' => 1, 'dibuat' => date('c'), 'catatan' => 'FIXTURE UJI dari salinan lokal — bukan data produksi', 'divisi' => $div, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
fwrite(STDERR, count($items) . " item ditulis ke $keluar\n");
