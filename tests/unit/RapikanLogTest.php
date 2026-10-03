<?php

use App\Commands\RapikanLog;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RapikanLogTest extends CIUnitTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/mic-rapikan-log-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
        parent::tearDown();
    }

    public function testHapusLogHarianLamaDanPangkasCron(): void
    {
        $sekarang = strtotime('2026-10-04 03:15:00');
        file_put_contents($this->dir . '/log-2026-07-01.log', 'lama');   // ±95 hari
        file_put_contents($this->dir . '/log-2026-08-10.log', 'baru');   // ±55 hari
        file_put_contents($this->dir . '/index.html', 'x');
        $isi = '';
        for ($i = 1; $i <= 300; $i++) {
            $isi .= "baris {$i}\n";
        }
        file_put_contents($this->dir . '/spi-snapshot-cron.log', $isi);
        file_put_contents($this->dir . '/kecil-cron.log', "a\nb\n");

        // Dry-run: tak ada yang berubah.
        $kering = RapikanLog::rapikan($this->dir, 60, 100, 1024, true, $sekarang);
        $this->assertSame(['log-2026-07-01.log'], $kering['dihapus']);
        $this->assertArrayHasKey('spi-snapshot-cron.log', $kering['dipangkas']);
        $this->assertFileExists($this->dir . '/log-2026-07-01.log');
        $this->assertSame($isi, file_get_contents($this->dir . '/spi-snapshot-cron.log'));

        $hasil = RapikanLog::rapikan($this->dir, 60, 100, 1024, false, $sekarang);
        $this->assertSame(['log-2026-07-01.log'], $hasil['dihapus']);
        $this->assertFileDoesNotExist($this->dir . '/log-2026-07-01.log');
        $this->assertFileExists($this->dir . '/log-2026-08-10.log');
        $this->assertFileExists($this->dir . '/index.html');

        $sisa = file($this->dir . '/spi-snapshot-cron.log', FILE_IGNORE_NEW_LINES);
        $this->assertCount(100, $sisa);
        $this->assertSame('baris 201', $sisa[0]);
        $this->assertSame('baris 300', $sisa[99]);
        $this->assertSame("a\nb\n", file_get_contents($this->dir . '/kecil-cron.log'));
    }

    public function testEkorTanpaBarisBaruDiAkhir(): void
    {
        $p = $this->dir . '/x-cron.log';
        file_put_contents($p, "1\n2\n3");
        $this->assertSame("2\n3", RapikanLog::ekor($p, 2));
        $this->assertSame("1\n2\n3", RapikanLog::ekor($p, 10));
    }
}
