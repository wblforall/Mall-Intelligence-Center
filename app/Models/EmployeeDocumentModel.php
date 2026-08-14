<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeDocumentModel extends Model
{
    protected $table         = 'employee_documents';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'employee_id', 'jenis', 'nama_dokumen', 'file_name', 'file_asli',
        'status', 'uploaded_by', 'reviewed_by', 'reviewed_at', 'catatan',
    ];
    protected $useTimestamps = true;

    public const JENIS = [
        'ktp'     => 'KTP',
        'npwp'    => 'NPWP',
        'kk'      => 'Kartu Keluarga',
        'ijazah'    => 'Ijazah',
        'transkrip' => 'Transkrip Nilai',
        'lainnya'   => 'Lainnya',
    ];

    /**
     * Dokumen yang WAJIB dimiliki setiap karyawan.
     *
     * "Wajib" di sini berarti dipantau dan ditagih, BUKAN memblokir akses:
     * saat aturan ini dibuat belum ada satu pun dokumen terunggah dari 202
     * karyawan, sehingga penegakan keras akan mengunci semua orang sekaligus.
     * Kelengkapannya tampil sebagai daftar periksa di /profile dan rekap di
     * sisi HR.
     */
    public const WAJIB = ['ktp', 'kk', 'npwp'];

    /**
     * Jenis yang hanya boleh SATU per karyawan.
     *
     * Praktis semuanya kecuali `lainnya`. Ijazah dan transkrip sempat
     * dikecualikan dengan alasan "bisa punya ijazah SMA dan S1", tapi MIC
     * hanya menyimpan pendidikan TERAKHIR — ijazah kedua tak punya tempat
     * untuk ditautkan, dan hanya menghasilkan tumpukan yang harus ditolak HR.
     * `lainnya` tetap bebas karena namanya diisi sendiri oleh karyawan.
     */
    public static function sekaliSaja(): array
    {
        return array_values(array_diff(array_keys(self::JENIS), ['lainnya']));
    }

    /**
     * Pasangan dokumen ↔ kolom nomor di tabel employees.
     *
     * Dipakai untuk menyandingkan keduanya di layar verifikasi HR: nomor dan
     * berkasnya diajukan lewat dua jalur berbeda (change request vs unggahan),
     * sehingga tanpa penyandingan ini HR bisa menyetujui nomor tanpa pernah
     * melihat kartunya, atau memverifikasi kartu sementara kolom nomornya
     * masih kosong tanpa ada yang menyadari.
     */
    public const PASANGAN_NOMOR = [
        'ktp'  => 'nik_ktp',
        'kk'   => 'no_kk',
        'npwp' => 'no_npwp',
    ];

    /** Kebalikan PASANGAN_NOMOR: kolom nomor → jenis dokumen. */
    public static function jenisUntukField(string $field): ?string
    {
        $peta = array_flip(self::PASANGAN_NOMOR);
        return $peta[$field] ?? null;
    }

    /**
     * Dokumen identitas milik sekumpulan karyawan, dikelompokkan
     * [employee_id][jenis]. Satu query untuk semua — layar verifikasi bisa
     * memuat puluhan baris dan pencarian per baris akan jadi N+1.
     *
     * Yang `approved` menang atas baris lain yang lebih baru, supaya dokumen
     * yang sudah sah tidak tertutup oleh unggahan gagal sesudahnya.
     */
    public function identitasUntukKaryawan(array $employeeIds): array
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        if (! $employeeIds) return [];

        $rows = $this->whereIn('employee_id', $employeeIds)
            ->whereIn('jenis', array_keys(self::PASANGAN_NOMOR))
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $peta = [];
        foreach ($rows as $r) {
            $eid   = (int) $r['employee_id'];
            $jenis = $r['jenis'];
            $ada   = $peta[$eid][$jenis] ?? null;
            if ($ada === null || ($r['status'] === 'approved' && $ada['status'] !== 'approved')) {
                $peta[$eid][$jenis] = $r;
            }
        }
        return $peta;
    }

    /**
     * Jenis yang sudah terpakai oleh karyawan ini → status-nya.
     *
     * Hanya `approved` dan `pending` yang menghalangi; yang DITOLAK justru
     * harus diunggah ulang sehingga pilihannya dibiarkan terbuka.
     *
     * @return array<string,string> jenis => 'approved'|'pending'
     */
    public function jenisTerpakai(int $employeeId): array
    {
        $rows = $this->select('jenis, status')
            ->where('employee_id', $employeeId)
            ->whereIn('jenis', self::sekaliSaja())
            ->whereIn('status', ['approved', 'pending'])
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            // 'approved' menang atas 'pending' bila keduanya ada.
            if (! isset($out[$r['jenis']]) || $r['status'] === 'approved') {
                $out[$r['jenis']] = $r['status'];
            }
        }
        return $out;
    }

    /**
     * Status kelengkapan dokumen wajib satu karyawan.
     *
     * Hanya dokumen berstatus `approved` yang dihitung lengkap — dokumen yang
     * masih menunggu verifikasi ditandai tersendiri supaya karyawan tidak
     * mengira urusannya sudah selesai padahal HR belum memeriksa.
     *
     * @return array{lengkap:bool, terverifikasi:int, total:int, per_jenis:array}
     */
    public function kelengkapanWajib(int $employeeId): array
    {
        $rows = $this->where('employee_id', $employeeId)
            ->whereIn('jenis', self::WAJIB)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $perJenis = [];
        foreach (self::WAJIB as $j) {
            $doc = null;
            foreach ($rows as $r) {
                // Baris terbaru per jenis menang; 'approved' mengalahkan
                // 'rejected' yang lebih baru agar dokumen yang sudah sah tidak
                // dianggap hilang hanya karena ada unggahan gagal sesudahnya.
                if ($r['jenis'] !== $j) continue;
                if ($doc === null || ($r['status'] === 'approved' && $doc['status'] !== 'approved')) $doc = $r;
            }
            $perJenis[$j] = [
                'label'  => self::JENIS[$j],
                'status' => $doc['status'] ?? null,   // null = belum diunggah
                'id'     => $doc['id'] ?? null,
            ];
        }

        $ok = count(array_filter($perJenis, static fn ($d) => $d['status'] === 'approved'));

        return [
            'lengkap'       => $ok === count(self::WAJIB),
            'terverifikasi' => $ok,
            'total'         => count(self::WAJIB),
            'per_jenis'     => $perJenis,
        ];
    }

    public static function jenisLabel(string $jenis, ?string $nama = null): string
    {
        if ($jenis === 'lainnya' && $nama) return $nama;
        return self::JENIS[$jenis] ?? ucfirst($jenis);
    }

    public function forEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)->orderBy('created_at', 'DESC')->findAll();
    }

    // Inbox HR: dokumen menunggu verifikasi, join nama karyawan
    public function pendingInbox(): array
    {
        return $this->db->table('employee_documents dc')
            ->select('dc.*, e.nama AS employee_nama, d.name AS dept_name')
            ->join('employees e', 'e.id = dc.employee_id', 'left')
            ->join('departments d', 'd.id = e.dept_id', 'left')
            ->where('dc.status', 'pending')
            ->orderBy('dc.created_at', 'ASC')
            ->get()->getResultArray();
    }

    public function countPending(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }
}
