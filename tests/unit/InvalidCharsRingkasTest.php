<?php

use App\Filters\InvalidCharsRingkas;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Filter invalidchars versi ringkas: tetap menolak, tapi pesannya tidak
 * memuat isi utuh nilai (log 245 MB pada 2 Okt 2026).
 *
 * @internal
 */
final class InvalidCharsRingkasTest extends CIUnitTestCase
{
    private function request(string $body): IncomingRequest
    {
        return new IncomingRequest(new App(), new URI('http://localhost/api/x'), $body, new UserAgent());
    }

    public function testBodyValidLolos(): void
    {
        $hasil = (new InvalidCharsRingkas())->before($this->request('nama=Andi%20%C3%87&catatan=baris1%0Abaris2'));
        $this->assertNull($hasil);
    }

    public function testUtf8RusakDitolakDenganPesanRingkas(): void
    {
        // Nilai 600 KB dengan byte non-UTF-8 (\xE9 Latin-1) di awal.
        $besar = 'judul=caf%E9' . str_repeat('x', 600 * 1024);

        try {
            (new InvalidCharsRingkas())->before($this->request($besar));
            $this->fail('Seharusnya ditolak.');
        } catch (SecurityException $e) {
            $pesan = $e->getMessage();
            $this->assertSame(400, $e->getCode());
            $this->assertStringContainsString('rawInput[judul]', $pesan);
            $this->assertStringContainsString('caf\xE9', $pesan);
            $this->assertLessThan(400, strlen($pesan), 'Pesan harus ringkas, bukan isi utuh.');
            $this->assertTrue(mb_check_encoding($pesan, 'UTF-8'));

            // Log CI4 mencetak argumen frame trace via var_export() — tidak
            // boleh ada frame yang memegang nilai mentah raksasa.
            foreach ($e->getTrace() as $frame) {
                foreach ($frame['args'] ?? [] as $arg) {
                    if (is_string($arg)) {
                        $this->assertLessThan(1000, strlen($arg), 'Nilai mentah bocor ke trace: ' . ($frame['function'] ?? '?'));
                    }
                }
            }
        }
    }

    public function testKarakterKontrolDitolakDanDiEscape(): void
    {
        try {
            (new InvalidCharsRingkas())->before($this->request('a%5Bb%5D=x%00y'));
            $this->fail('Seharusnya ditolak.');
        } catch (SecurityException $e) {
            $this->assertStringContainsString('rawInput[a.b]', $e->getMessage());
            $this->assertStringContainsString('x\x00y', $e->getMessage());
        }
    }

    public function testRingkasMemotongDanMenjagaUtf8(): void
    {
        $teks = str_repeat('é', 150); // 300 byte
        $r    = InvalidCharsRingkas::ringkas($teks, 200);
        $this->assertTrue(mb_check_encoding($r, 'UTF-8'));
        $this->assertSame(str_repeat('é', 100) . '…', $r);

        // Potongan jatuh di tengah karakter: byte sisa ditulis \xC3, bukan byte rusak.
        $this->assertSame('é\xC3…', InvalidCharsRingkas::ringkas('éé', 3));
    }
}
