<?php

namespace App\Services;

use App\Models\EmployeeCertificateModel;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Satu tempat menyimpan sertifikat karyawan, dipakai bersama oleh jalur HR
 * (langsung terverifikasi) dan jalur karyawan lewat ESS (menunggu verifikasi).
 *
 * Sengaja satu service, bukan dua salinan di masing-masing controller:
 * aturan validasi dan penanganan file yang terduplikat terbukti menyimpang
 * seiring waktu — persis yang terjadi pada aturan akses web vs API sebelum
 * disatukan ke MenuAccess.
 */
class EmployeeCertificateService
{
    private const DIR      = WRITEPATH . 'uploads/certificates/';
    private const MAKS_MB  = 10;
    private const EKSTENSI = ['jpg', 'jpeg', 'png', 'pdf'];

    /**
     * @param string $status 'approved' bila diinput HR, 'pending' bila diajukan karyawan.
     * @return array{ok:bool, msg:string, id:?int}
     */
    public function simpan(int $employeeId, array $post, ?UploadedFile $file, ?int $uploaderId, string $status): array
    {
        $nama = trim((string) ($post['nama_sertifikat'] ?? ''));
        if ($nama === '') return $this->gagal('Nama sertifikat wajib diisi.');

        if ($err = $this->validasi($post)) return $this->gagal($err);

        $fileName = $fileOrig = null;
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, self::EKSTENSI, true)) {
                return $this->gagal('Hanya file JPG, PNG, atau PDF.');
            }
            if ($file->getSize() > self::MAKS_MB * 1024 * 1024) {
                return $this->gagal('Ukuran file maksimal ' . self::MAKS_MB . ' MB.');
            }
            if (! is_dir(self::DIR)) mkdir(self::DIR, 0775, true);
            $fileName = 'cert_' . $employeeId . '_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $fileOrig = $file->getClientName();
            $file->move(self::DIR, $fileName);
            \App\Libraries\ImageCompressor::compress(self::DIR . '/' . $fileName);
        }

        $bersih = static fn ($k) => trim((string) ($post[$k] ?? '')) ?: null;

        $id = (new EmployeeCertificateModel())->insert([
            'employee_id'        => $employeeId,
            'nama_sertifikat'    => $nama,
            'jenis'              => $bersih('jenis'),
            'bidang'             => $bersih('bidang'),
            'level'              => $bersih('level'),
            'nomor_sertifikat'   => $bersih('nomor_sertifikat'),
            'penerbit'           => $bersih('penerbit'),
            'url_verifikasi'     => $bersih('url_verifikasi'),
            'pembiayaan'         => $bersih('pembiayaan'),
            'tanggal_terbit'     => $bersih('tanggal_terbit'),
            'tanggal_kadaluarsa' => $bersih('tanggal_kadaluarsa'),
            'file_name'          => $fileName,
            'file_original'      => $fileOrig,
            'catatan'            => $bersih('catatan'),
            'status'             => $status,
            'uploaded_by'        => $uploaderId,
            'reviewed_by'        => $status === 'approved' ? $uploaderId : null,
            'reviewed_at'        => $status === 'approved' ? date('Y-m-d H:i:s') : null,
        ]);

        return ['ok' => true, 'msg' => $status === 'pending'
            ? 'Sertifikat diajukan. Menunggu verifikasi HR.'
            : 'Sertifikat berhasil ditambahkan.', 'id' => (int) $id];
    }

    /** Hapus berkas fisik milik satu sertifikat. Dipanggil setelah baris dihapus. */
    public function hapusBerkas(?string $fileName): void
    {
        if (! $fileName) return;
        $path = self::DIR . $fileName;
        if (is_file($path)) @unlink($path);
    }

    private function validasi(array $post): ?string
    {
        $M = EmployeeCertificateModel::class;
        $v = static fn ($k) => trim((string) ($post[$k] ?? ''));

        foreach ([['jenis', $M::JENIS], ['level', $M::LEVEL], ['pembiayaan', $M::PEMBIAYAAN]] as [$field, $peta]) {
            if ($v($field) !== '' && ! isset($peta[$v($field)])) {
                return 'Pilihan ' . $field . ' tidak dikenal.';
            }
        }

        if ($v('url_verifikasi') !== '' && ! filter_var($v('url_verifikasi'), FILTER_VALIDATE_URL)) {
            return 'URL verifikasi tidak valid. Contoh: https://sertifikasi.bnsp.go.id/cek/12345';
        }

        // Kadaluarsa sebelum terbit hampir pasti salah ketik, dan kalau lolos
        // sertifikatnya langsung tampil "Kadaluarsa" tanpa sebab yang jelas.
        $terbit = $v('tanggal_terbit');
        $habis  = $v('tanggal_kadaluarsa');
        if ($terbit !== '' && $habis !== '' && strtotime($habis) < strtotime($terbit)) {
            return 'Tanggal kadaluarsa tidak boleh lebih awal dari tanggal terbit.';
        }
        if ($terbit !== '' && strtotime($terbit) > time()) {
            return 'Tanggal terbit tidak boleh di masa depan.';
        }

        return null;
    }

    private function gagal(string $msg): array
    {
        return ['ok' => false, 'msg' => $msg, 'id' => null];
    }
}
