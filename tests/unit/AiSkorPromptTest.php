<?php

use App\Libraries\AiSkorPrompt;
use PHPUnit\Framework\TestCase;

/**
 * Skor mutu prompt: rubrik, validasi JSON LLM, kelayakan. Tidak ada fallback
 * aturan yang menghasilkan skor — kegagalan LLM harus berujung NULL.
 *
 * @internal
 */
final class AiSkorPromptTest extends TestCase
{
    public function testRubrikLimaDimensiMaksimal100(): void
    {
        $this->assertCount(5, AiSkorPrompt::DIMENSI);
        $this->assertSame(100, count(AiSkorPrompt::DIMENSI) * AiSkorPrompt::MAKS_PER_DIMENSI);
        foreach (AiSkorPrompt::DIMENSI as $d) {
            $this->assertNotEmpty($d['nama']);
            $this->assertNotEmpty($d['lemah']);
            $this->assertNotEmpty($d['kuat']);
        }
        $sistem = AiSkorPrompt::promptSistem();
        foreach (array_keys(AiSkorPrompt::DIMENSI) as $k) {
            $this->assertStringContainsString($k, $sistem);
        }
    }

    public function testTingkatBatas(): void
    {
        $this->assertSame('perlu_dilatih', AiSkorPrompt::tingkat(39)[0]);
        $this->assertSame('cukup', AiSkorPrompt::tingkat(40)[0]);
        $this->assertSame('cukup', AiSkorPrompt::tingkat(69)[0]);
        $this->assertSame('baik', AiSkorPrompt::tingkat(70)[0]);
        $this->assertSame('baik', AiSkorPrompt::tingkat(84)[0]);
        $this->assertSame('sangat_baik', AiSkorPrompt::tingkat(85)[0]);
        $this->assertSame('sangat_baik', AiSkorPrompt::tingkat(100)[0]);
    }

    public function testParseJsonValid(): void
    {
        $r = AiSkorPrompt::parse('{"tujuan":18,"konteks":15,"kriteria":12,"kekhususan":16,"iterasi":19,"saran":"Sebutkan format hasil."}');
        $this->assertSame(80, $r['skor']);
        $this->assertSame(['tujuan' => 18, 'konteks' => 15, 'kriteria' => 12, 'kekhususan' => 16, 'iterasi' => 19], $r['rincian']);
        $this->assertSame('Sebutkan format hasil.', $r['saran']);
    }

    public function testParseToleranPagarKodeBulatkanDanBersarang(): void
    {
        $teks = "```json\n{\"rincian\":{\"tujuan\":\"17.6\",\"konteks\":10,\"kriteria\":10,\"kekhususan\":10,\"iterasi\":10}}\n```";
        $r = AiSkorPrompt::parse($teks);
        $this->assertSame(18, $r['rincian']['tujuan']);
        $this->assertSame(58, $r['skor']);
        $this->assertNull($r['saran']);
    }

    public function testParseTolakTidakValid(): void
    {
        $this->assertNull(AiSkorPrompt::parse('bukan json'));
        $this->assertNull(AiSkorPrompt::parse('{"tujuan":10,"konteks":10}'));                       // dimensi kurang
        $this->assertNull(AiSkorPrompt::parse('{"tujuan":"bagus","konteks":1,"kriteria":1,"kekhususan":1,"iterasi":1}'));
        $this->assertNull(AiSkorPrompt::parse('{"tujuan":85,"konteks":1,"kriteria":1,"kekhususan":1,"iterasi":1}')); // di luar rentang
    }

    public function testParseJepitRentang(): void
    {
        $r = AiSkorPrompt::parse('{"tujuan":20.4,"konteks":-0.4,"kriteria":20,"kekhususan":0,"iterasi":20}');
        $this->assertSame(20, $r['rincian']['tujuan']);
        $this->assertSame(0, $r['rincian']['konteks']);
        $this->assertSame(60, $r['skor']);
    }

    public function testKelayakan(): void
    {
        $this->assertFalse(AiSkorPrompt::layakDinilai([]));
        $this->assertFalse(AiSkorPrompt::layakDinilai(['Perbaiki laporan parkir bulanan di halaman MIC']), 'hanya 1 prompt');
        $this->assertFalse(AiSkorPrompt::layakDinilai(['lanjut', 'ok']), 'hanya lanjutan');
        $this->assertFalse(AiSkorPrompt::layakDinilai(['Ya, lanjutkan', 'oke']));
        $this->assertFalse(AiSkorPrompt::layakDinilai(['<command-name>/clear</command-name>', 'Tambah kolom catatan di form tenant']), 'pesan sistem tak dihitung');
        $this->assertTrue(AiSkorPrompt::layakDinilai(['Tambah kolom catatan di form tenant', 'ok']));
        $this->assertTrue(AiSkorPrompt::layakDinilai(['lanjut', 'Sekarang buat tes untuk fungsi itu']));
    }

    public function testCuplikanDibatasi(): void
    {
        $panjang = str_repeat('x', 5000);
        $prompt = [];
        for ($i = 1; $i <= 12; $i++) $prompt[] = "prompt {$i} " . $panjang;
        $c = AiSkorPrompt::cuplikan($prompt);
        $this->assertLessThanOrEqual(6, count($c));
        $this->assertLessThanOrEqual(4000, mb_strlen(implode('', $c)));
        $this->assertStringStartsWith('prompt 1 ', $c[0]);
    }

    public function testCuplikanPertahankanAwalDanAkhir(): void
    {
        $prompt = [];
        for ($i = 1; $i <= 10; $i++) $prompt[] = "p{$i}";
        $this->assertSame(['p1', 'p2', 'p3', 'p8', 'p9', 'p10'], AiSkorPrompt::cuplikan($prompt));
    }

    /** Jalankan $fn dengan env provider AI diganti sementara. */
    private function denganProvider(array $env, callable $fn): void
    {
        $kunci = [];
        for ($i = 1; $i <= 3; $i++) {
            foreach (['base_url', 'model', 'key'] as $f) $kunci[] = "aiklas.p{$i}_{$f}";
        }
        array_push($kunci, 'aiklas.base_url', 'aiklas.model', 'aiklas.api_key');
        $simpan = [];
        foreach ($kunci as $k) {
            $simpan[$k] = [array_key_exists($k, $_ENV) ? $_ENV[$k] : null, array_key_exists($k, $_SERVER) ? $_SERVER[$k] : null];
            $_ENV[$k] = $_SERVER[$k] = $env[$k] ?? '';
        }
        try {
            $fn();
        } finally {
            foreach ($simpan as $k => [$e, $sv]) {
                $e === null ? ($_ENV[$k] = '') : ($_ENV[$k] = $e);
                $sv === null ? ($_SERVER[$k] = '') : ($_SERVER[$k] = $sv);
            }
        }
    }

    public function testTanpaProviderTidakAdaSkor(): void
    {
        $this->denganProvider([], function () {
            $this->assertNull(AiSkorPrompt::nilai(['Perbaiki laporan parkir bulanan', 'Tambah kolom catatan di form tenant']));
        });
    }

    public function testProviderMatiTidakAdaSkorFallback(): void
    {
        // Provider tak terjangkau (semua gagal) → NULL, BUKAN skor heuristik.
        $this->denganProvider([
            'aiklas.p1_base_url' => 'http://127.0.0.1:9', 'aiklas.p1_model' => 'x', 'aiklas.p1_key' => 'x',
        ], function () {
            $this->assertTrue(\App\Libraries\AiKlasifikasi::terkonfigurasi());
            $prompt = [
                'Perbaiki rekap parkir bulanan di app/Controllers/Parkir.php agar total motor tidak ganda; format Rupiah.',
                'Tambahkan tes untuk fungsi itu, jangan ubah skema database.',
            ];
            $this->assertNull(AiSkorPrompt::nilai($prompt));
        });
    }

    public function testNilaiTidakPunyaMetodeAturan(): void
    {
        $this->assertFalse(method_exists(AiSkorPrompt::class, 'aturan'));
        $this->assertFalse(method_exists(AiSkorPrompt::class, 'fallback'));
    }
}
