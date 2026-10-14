<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Dokumen RAB satu periode (berversi). Status:
 *  draf → diajukan (isi dibekukan, PDF untuk e-Sign) → disetujui (admin E&P unggah PDF bertanda tangan).
 *  Revisi membuat versi baru (draf); versi lama tetap berlaku sampai versi baru disetujui,
 *  lalu menjadi 'digantikan'. Versi yang belum disetujui lalu direvisi menjadi 'batal'.
 */
class RabDokumenModel extends Model
{
    protected $table         = 'rab_dokumen';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['dept_id', 'induk_id', 'versi', 'nomor', 'periode_mulai', 'periode_selesai', 'status', 'snapshot',
                                'catatan', 'file_ttd', 'dibuat_by', 'diajukan_by', 'diajukan_at', 'disetujui_by', 'disetujui_at'];
    protected $useTimestamps = true;

    const AKTIF = ['diajukan', 'disetujui'];

    public function milikDept(int $deptId): array
    {
        return $this->select('rab_dokumen.id, dept_id, induk_id, versi, nomor, periode_mulai, periode_selesai, status, catatan, file_ttd,
                              dibuat_by, diajukan_at, disetujui_at, rab_dokumen.created_at, u.name AS pengaju, s.name AS penyetuju')
            ->join('users u', 'u.id = rab_dokumen.diajukan_by', 'left')
            ->join('users s', 's.id = rab_dokumen.disetujui_by', 'left')
            ->where('dept_id', $deptId)
            ->orderBy('periode_mulai', 'DESC')->orderBy('versi', 'DESC')->findAll();
    }

    /** Semua versi satu rantai RAB, terbaru dulu. */
    public function rantai(int $indukId): array
    {
        return $this->groupStart()->where('id', $indukId)->orWhere('induk_id', $indukId)->groupEnd()
            ->orderBy('versi', 'DESC')->findAll();
    }

    /** Ada RAB (rantai lain, belum batal) yang periodenya bersinggungan? */
    public function bentrok(int $deptId, string $mulai, string $selesai, ?int $kecualiInduk = null): ?array
    {
        $q = $this->where('dept_id', $deptId)->whereNotIn('status', ['batal', 'digantikan'])
            ->where('periode_mulai <=', $selesai)->where('periode_selesai >=', $mulai);
        if ($kecualiInduk) $q->where('COALESCE(induk_id, id) !=', $kecualiInduk, false);
        return $q->first();
    }

    /** RAB disetujui yang berlaku untuk bulan ini (versi terakhir yang disetujui). */
    public function berlaku(int $deptId, string $ym): ?array
    {
        return $this->where('dept_id', $deptId)->where('status', 'disetujui')
            ->where('periode_mulai <=', $ym)->where('periode_selesai >=', $ym)
            ->orderBy('versi', 'DESC')->first();
    }

    /**
     * Rentang periode yang TERKUNCI untuk perubahan angka item event: ada versi
     * diajukan/disetujui dan rantainya tidak sedang punya draf revisi.
     *
     * @return list<array{0:string,1:string,2:int}> [mulai, selesai, id dokumen]
     */
    public function periodeTerkunci(): array
    {
        $rows = $this->select('id, COALESCE(induk_id, id) AS rantai, periode_mulai, periode_selesai, status')
            ->whereIn('status', ['draf', 'diajukan', 'disetujui'])->findAll();
        $adaDraf = [];
        foreach ($rows as $r) if ($r['status'] === 'draf') $adaDraf[$r['rantai']] = true;
        $out = [];
        foreach ($rows as $r) {
            if (in_array($r['status'], self::AKTIF, true) && empty($adaDraf[$r['rantai']])) {
                $out[] = [$r['periode_mulai'], $r['periode_selesai'], (int) $r['id']];
            }
        }
        return $out;
    }

    /**
     * Item Event Content yang angkanya terkunci: tercantum di snapshot RAB
     * yang sedang aktif (tanpa draf revisi) → [item_id => nomor dokumen].
     * Item baru yang belum tercantum tidak terkunci — ia "di luar RAB".
     */
    public function itemTerkunci(): array
    {
        $out = [];
        foreach ($this->periodeTerkunci() as [, , $id]) {
            $d = $this->select('nomor, snapshot')->find($id);
            foreach (json_decode((string) ($d['snapshot'] ?? ''), true)['items'] ?? [] as $it) $out[(int) $it['id']] = $d['nomor'];
        }
        return $out;
    }

    /** Nomor dokumen, mis. RAB/EP/2026-11–12/v1. */
    public static function nomor(string $deptNama, string $mulai, string $selesai, int $versi): string
    {
        preg_match_all('/\b[A-Za-z]/', $deptNama, $m);
        $singkat = strtoupper(implode('', $m[0])) ?: 'DEPT';
        $per = $mulai === $selesai ? $mulai
            : (substr($mulai, 0, 4) === substr($selesai, 0, 4) ? $mulai . '–' . substr($selesai, 5, 2) : $mulai . '–' . $selesai);
        return "RAB/$singkat/$per/v$versi";
    }
}
