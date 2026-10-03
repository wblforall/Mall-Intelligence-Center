<?php

use App\Commands\PushDispatch;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * Antrean push yang lebih tua dari 24 jam ditandai kedaluwarsa, bukan dikirim.
 *
 * @internal
 */
final class PushDispatchKedaluwarsaTest extends CIUnitTestCase
{
    private $sqlite;

    private int $sekarang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sqlite = Database::connect([
            'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => '', 'DBDebug' => true,
        ], false);
        $this->sqlite->query("CREATE TABLE push_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INT, title VARCHAR(150),
            status VARCHAR(10) DEFAULT 'pending', attempts INT DEFAULT 0,
            last_error VARCHAR(255), created_at DATETIME, sent_at DATETIME)");

        $this->sekarang = strtotime('2026-10-04 12:00:00');
        $baris = [
            ['title' => 'basi',        'status' => 'pending', 'created_at' => date('Y-m-d H:i:s', $this->sekarang - 25 * 3600)],
            ['title' => 'segar',       'status' => 'pending', 'created_at' => date('Y-m-d H:i:s', $this->sekarang - 23 * 3600)],
            ['title' => 'sudah-kirim', 'status' => 'sent',    'created_at' => date('Y-m-d H:i:s', $this->sekarang - 72 * 3600)],
        ];
        foreach ($baris as $b) {
            $this->sqlite->table('push_queue')->insert($b + ['user_id' => 1]);
        }
    }

    private function baris(string $title): array
    {
        return $this->sqlite->table('push_queue')->where('title', $title)->get()->getRowArray();
    }

    public function testDryRunHanyaMenghitung(): void
    {
        $this->assertSame(1, PushDispatch::tandaiKedaluwarsa($this->sqlite, true, $this->sekarang));
        $this->assertSame('pending', $this->baris('basi')['status']);
    }

    public function testPendingBasiDitandaiSkipped(): void
    {
        $this->assertSame(1, PushDispatch::tandaiKedaluwarsa($this->sqlite, false, $this->sekarang));

        $basi = $this->baris('basi');
        $this->assertSame('skipped', $basi['status']);
        $this->assertSame(PushDispatch::ALASAN_KEDALUWARSA, $basi['last_error']);
        $this->assertSame('pending', $this->baris('segar')['status']);
        $this->assertSame('sent', $this->baris('sudah-kirim')['status']);
    }
}
