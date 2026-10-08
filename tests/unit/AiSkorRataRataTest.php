<?php

use App\Models\AiSessionModel;
use PHPUnit\Framework\TestCase;

/**
 * Rata-rata skor hanya dari sesi dinilai AI: sesi gagal-AI (NULL), dilewati,
 * dan pribadi tidak boleh masuk. Memakai DB lokal dalam transaksi yang di-rollback;
 * dilewati bila DB tak terjangkau atau migrasi skor belum dijalankan.
 *
 * @internal
 */
final class AiSkorRataRataTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $ada = false;
        try {
            $this->db = \Config\Database::connect('default'); // DB lokal (bukan grup 'tests')
            $ada = $this->db->fieldExists('skor_metode', 'ai_sessions');
        } catch (\Throwable $e) {
            $this->db = null;
            $this->markTestSkipped('DB tidak terjangkau: ' . $e->getMessage());
        }
        if (! $ada) {
            $this->db = null;
            $this->markTestSkipped('Migrasi skor belum dijalankan.');
        }
        $this->db->transBegin();
    }

    protected function tearDown(): void
    {
        if ($this->db) $this->db->transRollback();
    }

    private function sesi(int $emp, ?int $skor, ?string $metode, string $kantor, int $n, ?string $model = null): void
    {
        $this->db->table('ai_sessions')->insert([
            'device_id' => 999001, 'employee_id' => $emp, 'session_uuid' => 'T-' . $n . '-' . uniqid(),
            'klasifikasi_kantor' => $kantor, 'klasifikasi_at' => '2026-10-01 00:00:00',
            'terakhir_at' => '2026-10-02 10:00:00', 'jml_prompt' => 3,
            'skor_prompt' => $skor, 'skor_metode' => $metode, 'skor_model' => $model,
            'skor_rincian' => $skor === null ? null : json_encode(['tujuan' => 10, 'konteks' => 10, 'kriteria' => 10, 'kekhususan' => 10, 'iterasi' => 10]),
        ]);
    }

    public function testHanyaSesiDinilaiAiMasukRataRata(): void
    {
        $emp = 999002;
        $this->sesi($emp, 80, 'llm', 'kantor', 1);
        $this->sesi($emp, 60, 'llm', 'tak_jelas', 2);
        $this->sesi($emp, 10, 'llm', 'pribadi', 3);   // pribadi: tak boleh masuk walau ada skor
        $this->sesi($emp, null, null, 'kantor', 4);   // gagal-AI: belum dinilai
        $this->sesi($emp, null, 'lewati', 'kantor', 5); // dilewati: tak masuk, tak dihitung belum

        $r = (new AiSessionModel($this->db))->skorRingkas('2026-10-01', '2026-10-31', ['employee_id' => $emp]);
        $this->assertSame(2, $r['n']);
        $this->assertSame(70.0, $r['rata']);
        $this->assertSame(1, $r['belum']);
        $this->assertSame(1, $r['tak_jelas']);
    }

    public function testKaryawanDiBawahLimaSesiTanpaRata(): void
    {
        $emp = 999003;
        for ($i = 1; $i <= 4; $i++) $this->sesi($emp, 90, 'llm', 'kantor', $i);
        $baris = array_values(array_filter(
            (new AiSessionModel($this->db))->skorPerKaryawan('2026-10-01', '2026-10-31'),
            fn($r) => $r['employee_id'] === $emp
        ));
        // employees tak punya id ini → tetap muncul dengan nama cadangan, rata null.
        $this->assertSame(4, $baris[0]['n']);
        $this->assertNull($baris[0]['rata']);
    }

    public function testSkorPerModel(): void
    {
        $emp = 999004;
        $this->sesi($emp, 80, 'llm', 'kantor', 1, 'groq/qwen3.8-27b');
        $this->sesi($emp, 60, 'llm', 'kantor', 2, 'groq/qwen3.8-27b');
        $this->sesi($emp, 40, 'llm', 'kantor', 3, 'gemini/gemini-3.8-flash');
        $this->sesi($emp, 90, 'llm', 'pribadi', 4, 'gemini/gemini-3.8-flash'); // pribadi: tak dihitung
        $this->sesi($emp, null, null, 'kantor', 5, null);                      // gagal: tanpa model, tak dihitung
        $this->sesi($emp, null, 'lewati', 'kantor', 6, null);

        // Hanya baris uji ini (device 999001): saring model uji.
        $per = [];
        foreach ((new AiSessionModel($this->db))->skorPerModel('2026-10-01', '2026-10-31') as $m) $per[$m['model']] = $m;
        $this->assertSame(2, $per['groq/qwen3.8-27b']['n']);
        $this->assertSame(70.0, $per['groq/qwen3.8-27b']['rata']);
        $this->assertSame(1, $per['gemini/gemini-3.8-flash']['n']);
        $this->assertSame(40.0, $per['gemini/gemini-3.8-flash']['rata']);
        $this->assertSame(10.0, $per['groq/qwen3.8-27b']['dimensi']['tujuan']);
    }
}
