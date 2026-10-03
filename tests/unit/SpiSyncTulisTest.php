<?php

use App\Commands\SpiSync;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Logika tulis mic:spi-sync: sambung ulang + wait_timeout sebelum tiap blok,
 * hasil tiap tulis diperiksa, gagal dihitung (bukan "Selesai" palsu).
 * Memakai DB tiruan — tanpa MySQL & tanpa server SPI.
 *
 * @internal
 */
final class SpiSyncTulisTest extends CIUnitTestCase
{
    private function cmd(): SpiSync
    {
        $c = new SpiSync(service('logger'), service('commands'));
        $c->resetHasil();
        return $c;
    }

    private function fakeDb(bool $reconnectThrows = false): object
    {
        return new class ($reconnectThrows) {
            public $connID = true;
            public int $reconnects = 0;
            public int $initializes = 0;
            public array $queries = [];
            public function __construct(private bool $reconnectThrows) {}
            public function reconnect(): void
            {
                $this->reconnects++;
                if ($this->reconnectThrows) { throw new \ErrorException('MySQL server has gone away'); }
            }
            public function initialize(): void { $this->initializes++; $this->connID = true; }
            public function query(string $sql, $binds = null) { $this->queries[] = $sql; return true; }
            public function error(): array { return ['code' => 2006, 'message' => 'MySQL server has gone away']; }
        };
    }

    public function testSambungUlangDanWaitTimeoutSebelumTiapBlok(): void
    {
        $c  = $this->cmd();
        $db = $this->fakeDb();

        $c->tulisBlok($db, 'qty', [1, 2], fn($db, $r) => true);
        $c->tulisBlok($db, 'income', [1], fn($db, $r) => true);

        $this->assertSame(2, $db->reconnects, 'satu reconnect per blok tulis');
        $this->assertCount(2, $db->queries);
        $this->assertStringContainsString('wait_timeout = ' . SpiSync::DB_SESSION_TIMEOUT, $db->queries[0]);
        $this->assertStringContainsString('interactive_timeout', $db->queries[0]);
    }

    public function testBlokKosongTidakMenyentuhDb(): void
    {
        $c  = $this->cmd();
        $db = $this->fakeDb();
        $this->assertSame(0, $c->tulisBlok($db, 'qty', [], fn() => true));
        $this->assertSame(0, $db->reconnects);
    }

    public function testGagalTulisDihitungDanDicatat(): void
    {
        $c  = $this->cmd();
        $db = $this->fakeDb();

        // true = sukses, false = query gagal (DBDebug=false di produksi), null = dilewati,
        // exception = DBDebug=true / mysqli melempar.
        $hasil = [true, false, null, 'lempar', true];
        $ok = $c->tulisBlok($db, 'payment', $hasil, function ($db, $r) {
            if ($r === 'lempar') { throw new \RuntimeException('Deadlock found'); }
            return $r;
        });

        $this->assertSame(2, $ok);
        $this->assertSame(['ok' => 2, 'gagal' => 2], $c->getHasil()['payment']);
        $this->assertSame(2, $c->totalGagal());
        $this->assertStringContainsString('gone away', $c->getGalat()['payment'], 'galat pertama dari $db->error()');
        $this->assertStringContainsString('payment=2 (gagal 2)', $c->ringkasan());
    }

    public function testReconnectMelemparTetapMenyambungBaru(): void
    {
        $c  = $this->cmd();
        $db = $this->fakeDb(true);

        $c->tulisBlok($db, 'qty', [1], fn($db, $r) => true);

        $this->assertSame(1, $db->initializes, 'close() gagal → paksa connID=false lalu initialize()');
        $this->assertSame(['ok' => 1, 'gagal' => 0], $c->getHasil()['qty']);
        $this->assertSame(0, $c->totalGagal());
    }

    public function testGagalAmbilSpiDihitungDanSyncLanjut(): void
    {
        $c = $this->cmd();
        ob_start();
        $r = $c->ambil('income', '2026-09-01..2026-09-30', function () {
            throw new \RuntimeException('SPI /summary-daily-income-data: HTTP 500');
        });
        ob_end_clean();

        $this->assertNull($r);
        $this->assertSame(1, $c->getHasil()['income']['gagal']);
        $this->assertStringContainsString('HTTP 500', $c->getGalat()['income']);

        $this->assertSame([['x' => 1]], $c->ambil('qty', '-', fn() => [['x' => 1]]));
    }

    public function testRingkasanSemuaBerhasil(): void
    {
        $c  = $this->cmd();
        $db = $this->fakeDb();
        $c->tulisBlok($db, 'qty', [1, 2, 3], fn() => true);

        $this->assertSame(0, $c->totalGagal());
        $this->assertSame('qty=3 income=0 bulanan=0 payment=0 durasi=0 free=0 daily_vehicles=31', $c->ringkasan(31));
    }
}
