<?php

use App\Libraries\RabSusun as R;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan RAB Event & Promotion — tanpa DB. Kasus diambil dari RAB Nov–Des 2026.
 *
 * @internal
 */
final class RabSusunTest extends CIUnitTestCase
{
    private array $pos = [['id' => 1, 'nama' => 'Biaya Event'], ['id' => 2, 'nama' => 'Biaya Special Event'], ['id' => 3, 'nama' => 'Biaya Tiket & Akomodasi']];

    private function data(): array
    {
        $events = [
            ['id' => 10, 'name' => 'Regular Event eWalk November', 'jenis' => 'rutin', 'mall' => 'ewalk', 'start_date' => '2026-11-01', 'event_days' => 30, 'pos_id' => 1],
            ['id' => 11, 'name' => 'BSB Wedding Expo', 'jenis' => 'event', 'mall' => 'pentacity', 'start_date' => '2026-11-04', 'event_days' => 5, 'pos_id' => 2],
            ['id' => 12, 'name' => 'Christmas is Calling', 'jenis' => 'event', 'mall' => 'ewalk', 'start_date' => '2026-12-07', 'event_days' => 28, 'pos_id' => 2],
            ['id' => 13, 'name' => 'Bazar Multi Produk (tbc)', 'jenis' => 'event', 'mall' => 'pentacity', 'start_date' => '2026-11-09', 'event_days' => 14, 'pos_id' => null],
            ['id' => 14, 'name' => 'Event Oktober', 'jenis' => 'event', 'mall' => 'ewalk', 'start_date' => '2026-10-01', 'event_days' => 5, 'pos_id' => 1],
        ];
        $items = [
            // Live band 8 hari × 750rb; Biaya lain-lain → pos Tiket & Akomodasi
            ['id' => 1, 'event_id' => 10, 'nama' => 'Live Band Accoustic', 'tanggal' => null, 'qty' => 1, 'satuan' => 'Package', 'freq' => 8, 'satuan_freq' => 'Hari', 'harga_satuan' => 750000, 'budget' => 6000000, 'pos_id' => null, 'mall' => null],
            ['id' => 2, 'event_id' => 10, 'nama' => 'Biaya Lain-lain', 'tanggal' => null, 'qty' => 1, 'satuan' => 'Package', 'freq' => 1, 'satuan_freq' => 'Hari', 'harga_satuan' => 2500000, 'budget' => 0, 'pos_id' => 3, 'mall' => null],
            ['id' => 3, 'event_id' => 11, 'nama' => 'Konsumsi', 'kelompok' => 'Opening Ceremony', 'tanggal' => '2026-11-04', 'qty' => 100, 'satuan' => 'Package', 'freq' => 1, 'satuan_freq' => 'Hari', 'harga_satuan' => 35000, 'budget' => 0, 'pos_id' => null, 'mall' => null],
            // Item lama tanpa harga satuan → pakai budget
            ['id' => 4, 'event_id' => 12, 'nama' => 'Carnival Parade', 'tanggal' => '2026-12-05', 'qty' => null, 'freq' => null, 'harga_satuan' => null, 'budget' => 10000000, 'pos_id' => null, 'mall' => null],
            // Lintas tahun: item Januari tidak masuk RAB Nov–Des
            ['id' => 5, 'event_id' => 12, 'nama' => 'Late Night Sale Januari', 'tanggal' => '2027-01-02', 'qty' => 1, 'freq' => 1, 'harga_satuan' => 5000000, 'budget' => 0, 'pos_id' => null, 'mall' => null],
            // Event di luar periode
            ['id' => 6, 'event_id' => 14, 'nama' => 'MC', 'tanggal' => '2026-10-02', 'qty' => 1, 'freq' => 1, 'harga_satuan' => 300000, 'budget' => 0, 'pos_id' => null, 'mall' => null],
        ];
        $budget = [
            ['pos_id' => 1, 'tahun' => 2026, 'bulan' => 11, 'mall' => 'ewalk', 'jumlah' => 35000000],
            ['pos_id' => 2, 'tahun' => 2026, 'bulan' => 11, 'mall' => 'pentacity', 'jumlah' => 150000000],
            ['pos_id' => 3, 'tahun' => 2026, 'bulan' => 11, 'mall' => 'ewalk', 'jumlah' => 2500000],
            ['pos_id' => 2, 'tahun' => 2026, 'bulan' => 12, 'mall' => 'ewalk', 'jumlah' => 40000000],
            ['pos_id' => 1, 'tahun' => 2026, 'bulan' => 10, 'mall' => 'ewalk', 'jumlah' => 99],   // di luar periode
        ];
        return [$events, $items, $budget];
    }

    public function testTotalItemQtyFreqHargaAtauBudgetLama(): void
    {
        $this->assertSame(6000000.0, R::totalItem(['qty' => 1, 'freq' => 8, 'harga_satuan' => 750000, 'budget' => 1]));
        $this->assertSame(3500000.0, R::totalItem(['qty' => 100, 'freq' => null, 'harga_satuan' => 35000]));
        $this->assertSame(10000000.0, R::totalItem(['harga_satuan' => null, 'budget' => 10000000]));
    }

    public function testBulanMalDanPosIkutEventBilaItemKosong(): void
    {
        $ev = ['start_date' => '2026-12-07', 'mall' => 'keduanya', 'pos_id' => 2];
        $this->assertSame('2026-12', R::bulanItem(['tanggal' => null], $ev));
        $this->assertSame('2027-01', R::bulanItem(['tanggal' => '2027-01-02'], $ev));
        $this->assertSame('both', R::mallItem(['mall' => null], $ev));
        $this->assertSame('pentacity', R::mallItem(['mall' => 'pentacity'], $ev));
        $this->assertSame(2, R::posItem(['pos_id' => null], $ev));
        $this->assertSame(3, R::posItem(['pos_id' => 3], $ev));
        $this->assertNull(R::posItem([], ['pos_id' => null]));
    }

    public function testSnapshotHanyaItemDalamPeriodeDanEventTbcTetapTercantum(): void
    {
        [$events, $items, $budget] = $this->data();
        $s = R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12');
        $this->assertSame([1, 2, 3, 4], array_column($s['items'], 'id'));          // Jan & Okt tidak masuk
        $this->assertArrayHasKey(13, $s['events']);                                 // tbc tanpa item
        $this->assertArrayNotHasKey(14, $s['events']);                              // event Oktober
        $this->assertSame(3, $s['items'][1]['pos_id']);                             // pos item mengalahkan event
        $this->assertSame(1, $s['items'][0]['pos_id']);                             // ikut pos event
        $this->assertArrayNotHasKey('2026-10|ewalk|1', $s['budget']);
        $this->assertSame(150000000.0, $s['budget']['2026-11|pentacity|2']);
    }

    public function testRingkasanBudgetLawanRab(): void
    {
        [$events, $items, $budget] = $this->data();
        $r = R::ringkasan(R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12'));
        $this->assertSame(35000000.0, $r['sel']['2026-11']['ewalk'][1]['budget']);
        $this->assertSame(6000000.0, $r['sel']['2026-11']['ewalk'][1]['rab']);
        $this->assertSame(3500000.0, $r['sel']['2026-11']['pentacity'][2]['rab']);
        $this->assertSame(10000000.0, $r['sel']['2026-12']['ewalk'][2]['rab']);
        $this->assertSame(22000000.0, $r['total']['rab']);   // 6 + 2,5 + 3,5 + 10 jt
        $this->assertSame(227500000.0, $r['total']['budget']);
        $this->assertSame(8500000.0, $r['perBulanMal']['2026-11']['ewalk']['rab']);
    }

    public function testItemKeduanyaDibagiRata(): void
    {
        $this->assertSame(['ewalk' => 500.0, 'pentacity' => 500.0], R::bagiMal('both', 1000));
        // Sen ganjil: sama persis dengan Budget Departemen, jumlahnya tetap utuh.
        $b = R::bagiMal('both', 0.03);
        $this->assertEqualsWithDelta(0.03, $b['ewalk'] + $b['pentacity'], 1e-9);
        $this->assertSame(\App\Libraries\BudgetRekap::porsi('both', 0.03, 'ewalk'), $b['ewalk']);
    }

    public function testPeriksaMenghalangiItemTanpaPosAtauNol(): void
    {
        [$events, $items, $budget] = $this->data();
        $events[1]['pos_id'] = null;                      // Wedding Expo tanpa pos
        $items[0]['harga_satuan'] = 0;                    // live band 0
        $p = R::periksa(R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12'));
        $this->assertCount(2, $p['halang']);
        $this->assertStringContainsString('BSB Wedding Expo — Konsumsi: belum ada pos', $p['halang'][1] . $p['halang'][0]);
        $this->assertNotEmpty(array_filter($p['info'], fn ($x) => str_contains($x, 'Bazar Multi Produk')));
    }

    public function testPeriksaMencatatRabMelebihiBudget(): void
    {
        [$events, $items, $budget] = $this->data();
        $items[] = ['id' => 9, 'event_id' => 10, 'nama' => 'Band besar', 'tanggal' => null, 'qty' => 1, 'freq' => 1, 'harga_satuan' => 40000000, 'budget' => 0, 'pos_id' => null, 'mall' => null];
        $p = R::periksa(R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12'));
        $this->assertNotEmpty(array_filter($p['info'], fn ($x) => str_contains($x, 'November 2026 eWalk · Biaya Event: RAB 46.000.000 melebihi budget 35.000.000')));
    }

    public function testBedaRevisiBaruBerubahDihapus(): void
    {
        [$events, $items, $budget] = $this->data();
        $v1 = R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12');
        $items[0]['freq'] = 9;                                         // berubah
        unset($items[2]);                                              // dihapus
        $items[] = ['id' => 20, 'event_id' => 13, 'nama' => 'Sewa booth', 'tanggal' => '2026-11-10', 'qty' => 1, 'freq' => 1, 'harga_satuan' => 1000000, 'budget' => 0, 'pos_id' => 1, 'mall' => null];
        $v2 = R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12');
        $d = R::beda($v1, $v2);
        $this->assertSame([1 => 'berubah', 20 => 'baru'], $d['tanda']);
        $this->assertSame([3], array_column($d['dihapus'], 'id'));
        $this->assertSame(['tanda' => [], 'dihapus' => []], R::beda(null, $v2));
    }

    public function testLaporanRealisasiDenganItemDiLuarRab(): void
    {
        [$events, $items, $budget] = $this->data();
        $rab = R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-12');
        $items[] = ['id' => 21, 'event_id' => 10, 'nama' => 'Genset darurat', 'tanggal' => '2026-11-20', 'qty' => 1, 'freq' => 1, 'harga_satuan' => 1500000, 'budget' => 0, 'pos_id' => null, 'mall' => null];
        $live = R::snapshot($this->pos, $budget, $events, $items, '2026-11', '2026-11')['items'];
        $lap = R::laporan($rab, $live, [1 => 5250000, 3 => 3600000, 21 => 1500000, 4 => 999], '2026-11');
        $this->assertSame([1, 2, 21, 3], array_column($lap['baris'], 'id'));   // eWalk dulu, di luar RAB di akhir event-nya
        $luar = array_values(array_filter($lap['baris'], fn ($x) => $x['luar']))[0];
        $this->assertSame(21, $luar['id']);
        $this->assertSame(0.0, $luar['rab']);
        $this->assertSame(-100000.0, $lap['baris'][3]['sisa']);               // konsumsi lewat 100rb
        $this->assertSame(12000000.0, $lap['total']['rab']);
        $this->assertSame(10350000.0, $lap['total']['realisasi']);             // realisasi item Desember (id 4) tidak ikut
        $this->assertSame(['rab' => 6000000.0, 'realisasi' => 6750000.0], $lap['perPos'][1]);
    }

    public function testKunciEditHanyaMenahanFieldYangMengubahRab(): void
    {
        $ev = ['id' => 11, 'start_date' => '2026-11-04', 'event_days' => 5, 'mall' => 'pentacity', 'pos_id' => 2];
        $it = ['id' => 3, 'event_id' => 11, 'nama' => 'Konsumsi', 'tanggal' => '2026-11-04', 'qty' => 100, 'freq' => 1, 'harga_satuan' => 35000,
               'pic' => 'A', 'keterangan' => 'snack', 'waktu_mulai' => '10:00'];
        $beda = fn (array $ubah) => R::beda(['items' => [R::barisItem($it, $ev)]], ['items' => [R::barisItem($ubah + $it, $ev)]])['tanda'];
        $this->assertSame([], $beda(['pic' => 'B', 'waktu_mulai' => '11:00', 'tanggal' => '2026-11-06']));   // bulan sama → bebas
        $this->assertSame([3 => 'berubah'], $beda(['harga_satuan' => 40000]));
        $this->assertSame([3 => 'berubah'], $beda(['tanggal' => '2026-12-01']));                           // pindah bulan
        $this->assertSame([3 => 'berubah'], $beda(['pos_id' => 1]));
        $this->assertSame([], $beda(['keterangan' => 'snack box']));
    }

    public function testLabelPeriode(): void
    {
        $this->assertSame('November – Desember 2026', R::labelPeriode('2026-11', '2026-12'));
        $this->assertSame('Desember 2026 – Januari 2027', R::labelPeriode('2026-12', '2027-01'));
        $this->assertSame('November 2026', R::labelPeriode('2026-11', '2026-11'));
        $this->assertSame(['2026-12', '2027-01'], R::daftarBulan('2026-12', '2027-01'));
    }
}
