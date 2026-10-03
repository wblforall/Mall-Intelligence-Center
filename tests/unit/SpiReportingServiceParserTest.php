<?php

use App\Services\SpiReportingService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Parser endpoint SPI pengganti (summary-daily-income-data & casual-parking-data)
 * + aturan login ulang. Murni data tiruan — TIDAK menghubungi server SPI.
 *
 * @internal
 */
final class SpiReportingServiceParserTest extends CIUnitTestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(SUPPORTPATH . 'fixtures/spi/' . $name);
    }

    // ── summary-daily-income-data ────────────────────────────────

    public function testIncomeJsonDiparseDanTotalDihitungDariKolom(): void
    {
        $rows = SpiReportingService::parseDailyIncomeJson($this->fixture('summary-daily-income-data.json'));

        $this->assertCount(2, $rows, 'baris TOTAL harus dilewati');
        $this->assertSame([
            'tanggal' => '2026-09-01', 'mobil' => 12500000, 'motor' => 4321000, 'box' => 0,
            'truck' => 150000, 'taxi' => 0, 'bus' => 0, 'total' => 16971000,
        ], $rows[0]);
        $this->assertSame('2026-09-02', $rows[1]['tanggal']);
        $this->assertSame(4000000, $rows[1]['motor']);
        $this->assertSame(11000000 + 4000000 + 25000 + 10000, $rows[1]['total']);
    }

    public function testIncomeJsonKunciHurufKecilDanFormatPeriodeLain(): void
    {
        $raw  = '{"table":[{"periode":"01 Sep 2026","mobil":"1","motor":"2","box":"3","taxi":"4","bus":"5","truck":"6"}]}';
        $rows = SpiReportingService::parseDailyIncomeJson($raw);
        $this->assertSame('2026-09-01', $rows[0]['tanggal']);
        $this->assertSame(21, $rows[0]['total']);
    }

    public function testIncomeJsonTabelKosongBukanGalat(): void
    {
        $this->assertSame([], SpiReportingService::parseDailyIncomeJson('{"table":[]}'));
    }

    public function testIncomeBukanJsonDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        SpiReportingService::parseDailyIncomeJson('<html><body>Server Error</body></html>');
    }

    public function testIncomeTanpaKunciTableDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('table');
        SpiReportingService::parseDailyIncomeJson('{"data":[]}');
    }

    public function testIncomeNilaiBukanAngkaDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        SpiReportingService::parseDailyIncomeJson('{"table":[{"PERIODE":"2026-09-01","MOBIL":"n/a"}]}');
    }

    // ── casual-parking-data (HTML) ───────────────────────────────

    public function testCasualHtmlHeaderBertingkatDanKolomTambahan(): void
    {
        $ignored = null;
        $rows = SpiReportingService::parseCasualHtml($this->fixture('casual-parking-data.html'), $ignored);

        $this->assertSame(['2026-09-01', '2026-09-02', '2026-09-03'], array_column($rows, 'tanggal'));
        $this->assertSame([
            'Tunai' => 10500000, 'Flazz' => 1250000, 'e-Money' => 2000000, 'QRIS NISP' => 750000,
        ], $rows[0]['payments'], 'nol & "-" tidak disimpan; label sama dengan data lama');
        $this->assertSame(14500000, $rows[0]['income'], 'income dari kolom Total Income');
        $this->assertSame([
            'Tunai' => 9000000, 'Flazz' => 1000000, 'e-Money' => 1500000, 'BNI TapCash' => 250000,
            'QRIS NISP' => 500000,
        ], $rows[1]['payments']);
        $this->assertSame([], $rows[2]['payments']);
        $this->assertSame(['Kolom Baru'], $ignored, 'kolom tak dikenal dilaporkan, bukan bikin gagal');
        // bentuk keluaran lama dipertahankan
        $this->assertSame(['tanggal', 'income', 'qty', 'paid', 'free', 'payments'], array_keys($rows[0]));
        $this->assertSame(0, array_sum($rows[0]['qty']));
    }

    public function testCasualHtmlTanpaTheadRowspanDanBulanIndonesia(): void
    {
        $rows = SpiReportingService::parseCasualHtml($this->fixture('casual-parking-data-tanpa-thead.html'));

        $this->assertSame(['2026-08-31', '2026-09-01'], array_column($rows, 'tanggal'));
        $this->assertSame(['Tunai' => 150000, 'OVO' => 25000, 'Others' => 1000], $rows[0]['payments'],
            'dua baris per tanggal (rowspan) dijumlah');
        $this->assertSame(176000, $rows[0]['income'], 'tanpa kolom total → income = jumlah metode');
        $this->assertSame(['Tunai' => 70000], $rows[1]['payments']);
    }

    public function testCasualHtmlTabelKosongBukanGalat(): void
    {
        $html = '<table><thead><tr><th>Tanggal</th><th>Flazz</th></tr></thead>'
              . '<tbody><tr><td colspan="2">Tidak ada data</td></tr></tbody></table>';
        $this->assertSame([], SpiReportingService::parseCasualHtml($html));
    }

    public function testCasualHtmlTanpaTabelDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        SpiReportingService::parseCasualHtml('<html><body><h1>Whoops, something went wrong.</h1></body></html>');
    }

    public function testCasualHtmlStrukturTakDikenalDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('struktur tabel tak dikenali');
        SpiReportingService::parseCasualHtml(
            '<table><tr><th>Kode</th><th>Nilai</th></tr><tr><td>A</td><td>1</td></tr></table>');
    }

    public function testCasualHtmlSelBukanAngkaDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bukan angka');
        SpiReportingService::parseCasualHtml(
            '<table><tr><th>Tanggal</th><th>Flazz</th></tr><tr><td>2026-09-01</td><td>abc</td></tr></table>');
    }

    public function testCasualHtmlTanggalTakTerbacaDilempar(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tanggal tak terbaca');
        SpiReportingService::parseCasualHtml(
            '<table><tr><th>Tanggal</th><th>Flazz</th></tr><tr><td>Sep/1/26</td><td>1.000</td></tr></table>');
    }

    // ── util ────────────────────────────────────────────────────

    /** @dataProvider tanggalProvider */
    public function testNormalizeDate(string $in, ?string $want): void
    {
        $this->assertSame($want, SpiReportingService::normalizeDate($in));
    }

    public static function tanggalProvider(): array
    {
        return [
            ['2026-09-01', '2026-09-01'], ['2026-09-01 00:00:00', '2026-09-01'], ['01 Sep 2026', '2026-09-01'],
            ['1 Sep 2026', '2026-09-01'], ['01-Sep-2026', '2026-09-01'], ['01 September 2026', '2026-09-01'],
            ['01/09/2026', '2026-09-01'], ['15 Okt 2026', '2026-10-15'], ['Senin, 07 Des 2026', '2026-12-07'],
            ['TOTAL', null], ['', null], ['31 Feb 2026', null],
        ];
    }

    /** @dataProvider angkaProvider */
    public function testParseAmount($in, ?int $want): void
    {
        $this->assertSame($want, SpiReportingService::parseAmount($in));
    }

    public static function angkaProvider(): array
    {
        return [
            [1500, 1500], ['1500', 1500], ['1.234.500', 1234500], ['1,234,500', 1234500],
            ['Rp 1.234.500', 1234500], ['1.234.500,00', 1234500], ['12.50', 13], ['', 0], ['-', 0],
            ['-1.000', -1000], ['(2.000)', -2000], [null, 0], ['abc', null], ['1.23.4', null],
        ];
    }

    public function testFmtDmyUntukFormCasual(): void
    {
        $this->assertSame('01 Sep 2026', SpiReportingService::fmtDMY('2026-09-01'));
    }

    public function testSplitRange(): void
    {
        $this->assertSame(
            [['2026-09-01', '2026-09-07'], ['2026-09-08', '2026-09-14'], ['2026-09-15', '2026-09-16']],
            SpiReportingService::splitRange('2026-09-01', '2026-09-16', 7));
    }

    // ── aturan login ulang & retry ───────────────────────────────

    public function testLoginUlangHanyaBilaSesiDitolak(): void
    {
        $this->assertTrue(SpiReportingService::isSessionRejectedCode(401));
        $this->assertTrue(SpiReportingService::isSessionRejectedCode(419));
        $this->assertTrue(SpiReportingService::isSessionRejectedCode(302, 'http://spi/reporting2/public/index.php/login'));
        $this->assertFalse(SpiReportingService::isSessionRejectedCode(302, 'http://spi/reporting2/public/index.php/home'));
        $this->assertFalse(SpiReportingService::isSessionRejectedCode(500), '5xx bukan alasan login ulang');
        $this->assertFalse(SpiReportingService::isSessionRejectedCode(503));
        $this->assertFalse(SpiReportingService::isSessionRejectedCode(0), 'koneksi putus bukan alasan login ulang');
    }

    public function testDeteksiHalamanLogin(): void
    {
        $this->assertTrue(SpiReportingService::isLoginPage('<form method="POST" action="http://x/index.php/login"><input name="kduser"></form>'));
        $this->assertFalse(SpiReportingService::isLoginPage('<table><tr><th>Tanggal</th></tr></table>'));
        $this->assertFalse(SpiReportingService::isLoginPage('<form action="http://x/index.php/logout"></form>'));
    }

    public function testRetryHanyaUntukGalatTransien(): void
    {
        $this->assertTrue(SpiReportingService::isTransient(0));
        $this->assertTrue(SpiReportingService::isTransient(502));
        $this->assertTrue(SpiReportingService::isTransient(504));
        $this->assertFalse(SpiReportingService::isTransient(500), 'endpoint mati (500) tak perlu diulang');
        $this->assertFalse(SpiReportingService::isTransient(419));
    }

    public function testKredensialKosongMemberiGalatJelas(): void
    {
        $oldU = getenv('SPI_USER'); $oldP = getenv('SPI_PASS');
        $keep = [$_ENV['SPI_USER'] ?? null, $_ENV['SPI_PASS'] ?? null, $_SERVER['SPI_USER'] ?? null, $_SERVER['SPI_PASS'] ?? null];
        putenv('SPI_USER='); putenv('SPI_PASS=');
        $_ENV['SPI_USER'] = $_ENV['SPI_PASS'] = $_SERVER['SPI_USER'] = $_SERVER['SPI_PASS'] = '';
        try {
            $spi = new SpiReportingService();
            $this->assertFalse($spi->isConfigured());
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('SPI belum dikonfigurasi');
            $spi->ping();
        } finally {
            putenv($oldU === false ? 'SPI_USER' : "SPI_USER={$oldU}");
            putenv($oldP === false ? 'SPI_PASS' : "SPI_PASS={$oldP}");
            [$eu, $ep, $su, $sp] = $keep;
            if ($eu === null) { unset($_ENV['SPI_USER']); } else { $_ENV['SPI_USER'] = $eu; }
            if ($ep === null) { unset($_ENV['SPI_PASS']); } else { $_ENV['SPI_PASS'] = $ep; }
            if ($su === null) { unset($_SERVER['SPI_USER']); } else { $_SERVER['SPI_USER'] = $su; }
            if ($sp === null) { unset($_SERVER['SPI_PASS']); } else { $_SERVER['SPI_PASS'] = $sp; }
        }
    }
}
